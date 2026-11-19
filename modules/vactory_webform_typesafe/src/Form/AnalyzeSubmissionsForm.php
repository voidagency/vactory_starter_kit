<?php

namespace Drupal\vactory_webform_typesafe\Form;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Queues historical submissions in bounded, access-checked batches.
 */
final class AnalyzeSubmissionsForm extends AnalysisFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_webform_typesafe_backfill';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WebformInterface $webform = NULL) {
    $form_state->set('webform_id', $webform->id());
    $enabled = (bool) $this->manager->settings($webform);
    $form['help'] = [
      '#markup' => $enabled ? $this->t('This operation queues matching completed submissions in batches. Actual AI processing runs through cron. Successful reanalysis replaces previous corrections.') : $this->t('Add and enable a TypeSafe classification handler for this Webform first.'),
    ];
    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Analyze'),
      '#options' => [
        'missing' => $this->t('Submissions without an analysis'),
        'failed' => $this->t('Failed analyses'),
        'outdated' => $this->t('Outdated analyses'),
        'all' => $this->t('All completed submissions'),
      ],
      '#default_value' => 'missing',
    ];
    $form['from'] = [
      '#type' => 'date',
      '#title' => $this->t('Created on or after (UTC)'),
    ];
    $form['to'] = [
      '#type' => 'date',
      '#title' => $this->t('Created on or before (UTC)'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Queue submissions'),
      '#disabled' => !$enabled,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('from') && $form_state->getValue('to') && $form_state->getValue('from') > $form_state->getValue('to')) {
      $form_state->setErrorByName('to', $this->t('The end date must be on or after the start date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $wid = $form_state->get('webform_id');
    $max = $this->entities->getStorage('webform_submission')->getQuery()->accessCheck(FALSE)->condition('webform_id', $wid)->sort('sid', 'DESC')->range(0, 1)->execute();
    batch_set([
      'title' => $this->t('Queueing submissions'),
      'operations' => [[
    [static::class, 'queueBatch'],
    [
      $wid,
      $form_state->getValue('mode'),
      $form_state->getValue('from'),
      $form_state->getValue('to'),
      $max ? (int) reset($max) : 0,
    ],
      ],
      ],
      'finished' => [static::class, 'finished'],
    ]);
    $form_state->setRedirect('vactory_webform_typesafe.results', [
      'webform' => $wid,
    ]);
  }

  /**
   * Queues up to fifty historical submissions per batch iteration.
   */
  public static function queueBatch(string $wid, string $mode, string $from, string $to, int $max, array &$context): void {
    $context['sandbox'] += ['cursor' => 0];
    $context['results'] += ['queued' => 0, 'skipped' => 0, 'failed' => 0];
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($wid);
    if (!$webform || !\Drupal::currentUser()->hasPermission('run vactory webform typesafe') || !$webform->access('submission_view_any')) {
      throw new AccessDeniedHttpException();
    }
    $manager = \Drupal::service('vactory_webform_typesafe.manager');
    $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $ids = self::nextSubmissionIds($wid, $from, $to, (int) $context['sandbox']['cursor'], $max);
    foreach ($storage->loadMultiple($ids) as $sid => $submission) {
      $context['sandbox']['cursor'] = (int) $sid;
      if (!$submission->access('view')) {
        $context['results']['skipped']++;
        continue;
      }
      try {
        $record = $manager->load((int) $sid);
        $queued = self::matchesMode($mode, $record, $submission) && $manager->enqueue($submission, $mode !== 'missing');
        $context['results'][$queued ? 'queued' : 'skipped']++;
      }
      catch (\Throwable) {
        $context['results']['failed']++;
      }
    }
    $context['message'] = t('Queued @queued; skipped @skipped; failed @failed.', array_combine([
      '@queued',
      '@skipped',
      '@failed',
    ], array_values($context['results'])));
    $context['finished'] = count($ids) < 50 ? 1 : min(0.99, $context['sandbox']['cursor'] / max(1, $max));
  }

  /**
   * IDs of the next fifty completed submissions in the selected range.
   */
  private static function nextSubmissionIds(string $wid, string $from, string $to, int $cursor, int $max): array {
    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery()
      ->accessCheck(FALSE)
      ->condition('webform_id', $wid)
      ->condition('in_draft', 0)
      ->condition('sid', $cursor, '>')
      ->condition('sid', $max, '<=')
      ->sort('sid')
      ->range(0, 50);
    if ($from) {
      $query->condition('created', strtotime($from . ' 00:00:00 UTC'), '>=');
    }
    if ($to) {
      $query->condition('created', strtotime($to . ' 23:59:59 UTC'), '<=');
    }
    return $query->execute();
  }

  /**
   * Whether a submission matches the selected queueing mode.
   */
  private static function matchesMode(string $mode, $record, $submission): bool {
    return match ($mode) {
      'missing' => !$record,
      'failed' => $record && $record->get('status')->value === 'failed',
      'outdated' => $record && self::isOutdated($record, $submission),
      'all' => TRUE,
      default => FALSE,
    };
  }

  /**
   * Whether an analysis no longer matches the submission or is stale.
   */
  private static function isOutdated($record, $submission): bool {
    $fingerprint = \Drupal::service('vactory_webform_typesafe.manager')->snapshot($submission)['fingerprint'] ?? '';
    return $fingerprint !== $record->get('fingerprint')->value || $record->get('status')->value === 'stale';
  }

  /**
   * Reports batch totals without exposing submitted content.
   */
  public static function finished($success, $results): void {
    if ($success) {
      \Drupal::messenger()->addStatus(t('Queued @queued submissions. Skipped @skipped; failed @failed. Run cron to process queued analyses.', [
        '@queued' => $results['queued'] ?? 0,
        '@skipped' => $results['skipped'] ?? 0,
        '@failed' => $results['failed'] ?? 0,
      ]));
    }
    else {
      \Drupal::messenger()->addError(t('Queueing stopped. Already queued submissions can still be processed.'));
    }
  }

}
