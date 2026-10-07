<?php

namespace Drupal\vactory_webform_typesafe\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\vactory_webform_typesafe\Exception\JevApiException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Coordinates deduplicated queue work and current analysis storage.
 */
final class AnalysisManager {

  public const QUEUE = 'vactory_webform_typesafe';

  /**
   * Constructs the service with its injected dependencies.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entities,
    private readonly QueueFactory $queues,
    private readonly ConfigFactoryInterface $config,
    private readonly LockBackendInterface $lock,
    private readonly SubmissionInputBuilder $input,
    private readonly TypeSafeClassifierInterface $classifier,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Returns settings from the enabled classification handler.
   */
  public function settings(WebformInterface $webform): ?array {
    foreach ($webform->getHandlers('vactory_typesafe', TRUE) as $handler) {
      return $handler->getConfiguration()['settings'];
    }
    return NULL;
  }

  /**
   * Loads a fresh analysis record for a submission.
   */
  public function load(int $sid) {
    $storage = $this->entities->getStorage('vactory_webform_analysis');
    $storage->resetCache([$sid]);
    return $storage->load($sid);
  }

  /**
   * Builds current input and fingerprints the effective configuration.
   */
  public function snapshot(WebformSubmissionInterface $submission): ?array {
    $settings = $this->settings($submission->getWebform());
    if (!$settings || $submission->isDraft()) {
      return NULL;
    }
    $defaults = $this->config->get('vactory_webform_typesafe.settings');
    $state = $this->input->build($submission, $settings['fields'], (int) $defaults->get('max_input_bytes'));
    if (!$state) {
      return NULL;
    }
    $questions = QuestionDefinition::parse($settings['questions']);
    $model = trim($settings['model'] ?? '') ?: $defaults->get('model');
    $fingerprint = hash('sha256', json_encode([$state, $questions, $model], JSON_THROW_ON_ERROR));
    return compact('state', 'questions', 'model', 'fingerprint');
  }

  /**
   * Queues eligible content, optionally replacing a previous generation.
   */
  public function enqueue(WebformSubmissionInterface $submission, bool $force = FALSE): bool {
    $sid = (int) $submission->id();
    $key = self::QUEUE . ':save:' . $sid;
    if (!$sid || !$this->lock->acquire($key, 30)) {
      return FALSE;
    }
    try {
      $snapshot = $this->snapshot($submission);
      if (!$snapshot) {
        return FALSE;
      }
      $record = $this->load($sid);
      if (!$force && $record && $record->get('fingerprint')->value === $snapshot['fingerprint'] && in_array($record->get('status')->value, [
        'pending',
        'processing',
        'completed',
      ], TRUE)) {
        return FALSE;
      }
      $record ??= $this->entities->getStorage('vactory_webform_analysis')->create([
        'id' => $sid,
        'submission' => $sid,
        'webform' => $submission->getWebform()->id(),
      ]);
      $generation = bin2hex(random_bytes(16));
      foreach ([
        'fingerprint' => $snapshot['fingerprint'],
        'generation' => $generation,
        'model' => $snapshot['model'],
        'questions' => json_encode($snapshot['questions'], JSON_THROW_ON_ERROR),
        'status' => 'pending',
        'review_status' => 'unreviewed',
        'attempts' => 0,
        'error' => '',
        'changed' => $this->time->getCurrentTime(),
      ] as $field => $value) {
        $record->set($field, $value);
      }
      // Retain previous results and corrections until a successful replacement.
      $record->set('labels', []);
      $record->save();
      if (!$this->queues->get(self::QUEUE)->createItem([
        'sid' => $sid,
        'generation' => $generation,
      ])) {
        $record->set('status', 'failed')->set('error', 'Could not create a queue item.')->save();
        return FALSE;
      }
      return TRUE;
    }
    finally {
      $this->lock->release($key);
    }
  }

  /**
   * Processes one queue generation and rejects stale responses.
   */
  public function process(array $item): void {
    $sid = (int) ($item['sid'] ?? 0);
    $work_key = self::QUEUE . ':work:' . $sid;
    if (!$this->lock->acquire($work_key, 300)) {
      throw new DelayedRequeueException(60);
    }
    try {
      try {
        $snapshot = $this->prepare($item);
        if (!$snapshot) {
          return;
        }
        $result = $this->classifier->classify($snapshot['state'], $snapshot['questions'], $snapshot['model']);
      }
      catch (DelayedRequeueException $e) {
        throw $e;
      }
      catch (\Throwable $e) {
        $this->recordFailure($item, $e instanceof JevApiException ? $e : NULL);
        return;
      }
      $save_key = self::QUEUE . ':save:' . $sid;
      if (!$this->lock->acquire($save_key, 30)) {
        throw new DelayedRequeueException(60);
      }
      try {
        $current = $this->load($sid);
        if (!$current || $current->get('generation')->value !== $item['generation']) {
          return;
        }
        $submission = $this->freshSubmission($sid);
        if (!$submission) {
          $current->delete();
          return;
        }
        try {
          $latest = $this->snapshot($submission);
        }
        catch (\Throwable) {
          $latest = NULL;
        }
        if (!$latest || $latest['fingerprint'] !== $snapshot['fingerprint']) {
          $current->set('status', 'stale')->set('error', 'Input changed during analysis. Reanalyze this submission.')->save();
          return;
        }
        $labels = [];
        foreach ($result['answers'] as $id => $answer) {
          $labels[] = $id . ':' . QuestionDefinition::value($snapshot['questions'][$id], $answer);
        }
        $current->set('labels', $labels)
          ->set('results', json_encode($result, JSON_THROW_ON_ERROR))
          ->set('model', $result['model'])->set('status', 'completed')
          ->set('review_status', QuestionDefinition::needsReview($snapshot['questions'], $result['answers']) ? 'needs_review' : 'unreviewed')
          ->set('overrides', '{}')->set('reviewer', 0)->set('reviewed', 0)
          ->set('error', '')->set('changed', $this->time->getCurrentTime())->save();
      }
      finally {
        $this->lock->release($save_key);
      }
    }
    finally {
      $this->lock->release($work_key);
    }
  }

  /**
   * Claims a current generation under the same lock used by enqueue and review.
   */
  private function prepare(array $item): ?array {
    $sid = (int) $item['sid'];
    $key = self::QUEUE . ':save:' . $sid;
    if (!$this->lock->acquire($key, 30)) {
      throw new DelayedRequeueException(60);
    }
    try {
      $record = $this->load($sid);
      if (!$record || $record->get('generation')->value !== ($item['generation'] ?? '') || !in_array($record->get('status')->value, [
        'pending',
        'processing',
      ], TRUE)) {
        return NULL;
      }
      $submission = $this->freshSubmission($sid);
      if (!$submission) {
        $record->delete();
        return NULL;
      }
      $attempts = (int) $record->get('attempts')->value;
      if ($attempts >= (int) $this->config->get('vactory_webform_typesafe.settings')->get('max_attempts')) {
        $record->set('status', 'failed')->set('error', 'Maximum processing attempts reached.')->save();
        return NULL;
      }
      // Count input failures toward the attempt limit too.
      $record->set('status', 'processing')->set('attempts', $attempts + 1)->save();
      $snapshot = $this->snapshot($submission);
      if (!$snapshot || $snapshot['fingerprint'] !== $record->get('fingerprint')->value) {
        $record->set('status', 'stale')->set('error', 'Submission or configuration changed. Reanalyze to use current settings.')->save();
        return NULL;
      }
      return $snapshot;
    }
    finally {
      $this->lock->release($key);
    }
  }

  /**
   * Records a bounded retry without overwriting a newer queue generation.
   */
  private function recordFailure(array $item, ?JevApiException $failure = NULL): void {
    $sid = (int) $item['sid'];
    $key = self::QUEUE . ':save:' . $sid;
    if (!$this->lock->acquire($key, 30)) {
      throw new DelayedRequeueException(60);
    }
    try {
      $record = $this->load($sid);
      if (!$record || $record->get('generation')->value !== $item['generation']) {
        return;
      }
      $attempts = (int) $record->get('attempts')->value;
      $retry = ($failure?->retryable ?? TRUE) && $attempts < (int) $this->config->get('vactory_webform_typesafe.settings')->get('max_attempts');
      $record->set('status', $retry ? 'pending' : 'failed')
        ->set('error', $failure?->getMessage() ?? 'Classification failed. Check the connection, selected fields and input limits.')
        ->set('changed', $this->time->getCurrentTime())->save();
      // Never log raw provider exceptions, which may include submitted content.
      if ($retry) {
        throw new DelayedRequeueException(max($failure?->retryAfter ?? 0, min(3600, 60 * (2 ** max(0, $attempts - 1)))));
      }
    }
    finally {
      $this->lock->release($key);
    }
  }

  /**
   * Reloads the submission and its Webform after potentially long API requests.
   */
  private function freshSubmission(int $sid): ?WebformSubmissionInterface {
    $storage = $this->entities->getStorage('webform_submission');
    $storage->resetCache([$sid]);
    $submission = $storage->load($sid);
    if ($submission) {
      $wid = $submission->getWebform()->id();
      $this->entities->getStorage('webform')->resetCache([$wid]);
      $storage->resetCache([$sid]);
      $submission = $storage->load($sid);
    }
    return $submission;
  }

}
