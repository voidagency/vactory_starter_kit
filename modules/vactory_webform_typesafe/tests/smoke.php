<?php

/**
 * @file
 * Isolated integration checks. Run with drush php:script tests/smoke.php.
 *
 * Creates synthetic fixtures and deletes them in finally. Never calls TypeSafe.
 */

use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\vactory_webform_typesafe\Batch\ProcessPendingBatch;
use Drupal\vactory_webform_typesafe\Service\JevResponseValidator;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\vactory_webform_typesafe\Access\AnalysisAccess;
use Drupal\vactory_webform_typesafe\Form\AnalyzeSubmissionsForm;
use Drupal\vactory_webform_typesafe\Form\ClassificationResultsForm;
use Drupal\vactory_webform_typesafe\Form\ReviewAnalysisForm;
use Drupal\vactory_webform_typesafe\Form\SettingsForm;
use Drupal\vactory_webform_typesafe\Service\AnalysisManager;
use Drupal\vactory_webform_typesafe\Service\QuestionDefinition;
use Drupal\vactory_webform_typesafe\Service\TypeSafeClassifierInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\user\Entity\User;

$checks = 0;

$check = static function ($condition, string $message) use (&$checks): void {
  if (!$condition) {
    throw new \RuntimeException('FAIL: ' . $message);
  }
  $checks++;
  print "PASS: $message\n";
};
$container = \Drupal::getContainer();
$entities = \Drupal::entityTypeManager();
$fixture = 'typesafe_test_' . bin2hex(random_bytes(4));
$webform = NULL;
$submission_ids = [];
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(User::load(1));
$fake = new class implements TypeSafeClassifierInterface {
  public int $calls = 0;
  public bool $fail = FALSE;
  public $duringCall;

  public function classify(array $state, array $questions, string $model): array {
    $this->calls++;
    if ($this->duringCall) {
      ($this->duringCall)();
    }
    if ($this->fail) {
      throw new \RuntimeException('DO NOT STORE THIS PRIVATE MESSAGE');
    }
    $answers = [];
    foreach ($questions as $id => $q) {
      if ($q['type'] === 'noul') {
        $answers[$id] = ['type' => 'noul', 'noul' => 0.5];
      }
      elseif ($q['type'] === 'score') {
        $answers[$id] = ['type' => 'score', 'score' => 1.0, 'confidence' => 0.9, 'probabilities' => [0.0, 1.0, 0.0], 'legend' => $q['criteria']];
      }
      else {
        $options = array_fill_keys(array_keys($q['criteria']), 0.0);
        $first = array_key_first($options);
        $options[$first] = 1.0;
        $answers[$id] = ['type' => 'choice', 'choice' => $first, 'confidence' => 0.9, 'probabilities' => $options];
      }
    }
    return JevResponseValidator::validate(['model' => 'test-model', 'answers' => $answers, 'usage' => ['input_tokens' => 10, 'output_tokens' => 5]], $questions);
  }

};
$manager = new AnalysisManager($entities, \Drupal::service('queue'), \Drupal::configFactory(), \Drupal::lock(), \Drupal::service('vactory_webform_typesafe.input'), $fake, \Drupal::time());
$item = static fn($record) => ['sid' => (int) $record->id(), 'generation' => $record->get('generation')->value];
try {
  $questions = QuestionDefinition::defaults();
  $check(count(QuestionDefinition::parse(json_encode($questions))) === 4, 'Default questions validate with the custom Jev schema');
  $invalid = $questions;
  $invalid['callback']['review_min'] = 0.8;
  try {
    QuestionDefinition::parse(json_encode($invalid));
    $rejected = FALSE;
  }
  catch (InvalidArgumentException) {
    $rejected = TRUE;
  }
  $check($rejected, 'Invalid Noul uncertainty interval is rejected');

  $webform = Webform::create(['id' => $fixture, 'title' => 'TypeSafe isolated test', 'status' => 'open']);
  $webform->setElements(['message' => ['#type' => 'textarea', '#title' => 'Message'], 'secret' => ['#type' => 'password', '#title' => 'Secret'], 'upload' => ['#type' => 'managed_file', '#title' => 'File']]);
  $webform->save();
  $handler = \Drupal::service('plugin.manager.webform.handler')->createInstance('vactory_typesafe', ['handler_id' => 'classification', 'status' => TRUE, 'settings' => ['fields' => ['message'], 'automatic' => FALSE, 'on_update' => FALSE, 'model' => '', 'questions' => json_encode($questions)]]);
  $handler->setWebform($webform);
  $webform->addWebformHandler($handler);
  $webform->save();
  $submission = WebformSubmission::create(['webform_id' => $fixture, 'data' => ['message' => 'Please refund a duplicate payment.', 'secret' => 'sensitive']]);
  $submission->save();
  $sid = (int) $submission->id();
  $submission_ids[] = $sid;
  $snapshot = $manager->snapshot($submission);
  $check(array_keys($snapshot['state']) === ['message'], 'Only selected eligible text fields enter the API state');
  $builder = \Drupal::service('vactory_webform_typesafe.input');
  $check(!isset($builder->eligibleFields($webform)['secret']) && !isset($builder->eligibleFields($webform)['upload']), 'Passwords and uploads cannot be selected');
  try {
    $builder->build($submission, ['message'], 5);
    $rejected = FALSE;
  }
  catch (LengthException) {
    $rejected = TRUE;
  }
  $check($rejected, 'Oversized input is rejected without truncation');

  $check($manager->enqueue($submission), 'Submission is queued');
  $check(!$manager->enqueue($submission), 'Duplicate queue request is skipped');
  $record = $manager->load($sid);
  $queued_item = $item($record);
  $original_manager = $container->get('vactory_webform_typesafe.manager');
  $container->set('vactory_webform_typesafe.manager', $manager);
  try {
    $context = ['results' => []];
    ProcessPendingBatch::process($fixture, $sid, $context);
    $check($context['results']['completed'] === 1, 'Immediate batch completes a pending submission');
    $calls = $fake->calls;
    ProcessPendingBatch::process($fixture, $sid, $context);
    $manager->process($queued_item);
    $check($fake->calls === $calls, 'Batch and later cron skip already completed work');

    $manager->enqueue($submission, TRUE);
    $other_webform = Webform::create(['id' => $fixture . '_other', 'title' => 'Other isolated test']);
    $other_webform->save();
    try {
      ProcessPendingBatch::process($other_webform->id(), $sid, $context);
    }
    finally {
      $other_webform->delete();
    }
    $check($fake->calls === $calls, 'Batch does not process another Webform submission');
    $fake->fail = TRUE;
    ProcessPendingBatch::process($fixture, $sid, $context);
    $check($manager->load($sid)->get('status')->value === 'pending' && $context['results']['pending'] === 1, 'Retryable failures remain pending for cron');
    $fake->fail = FALSE;
    ProcessPendingBatch::process($fixture, $sid, $context);
    $check($manager->load($sid)->get('status')->value === 'completed', 'Pending retry can be completed immediately');

    $switcher->switchTo(new AnonymousUserSession());
    try {
      ProcessPendingBatch::process($fixture, $sid, $context);
      $denied = FALSE;
    }
    catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
      $denied = TRUE;
    }
    finally {
      $switcher->switchBack();
    }
    $check($denied, 'Immediate batch rechecks processing permissions');
    $definition = \Drupal::service('plugin.manager.queue_worker')->getDefinition(AnalysisManager::QUEUE);
    $check(($definition['cron']['time'] ?? 0) === 30, 'Cron processing remains enabled');
  }
  finally {
    $fake->fail = FALSE;
    $container->set('vactory_webform_typesafe.manager', $original_manager);
  }
  $record = $manager->load($sid);
  $check($record->get('status')->value === 'completed', 'Queued analysis saves a completed result');
  $check($record->get('review_status')->value === 'needs_review', 'Uncertain Noul probability requires review');
  $check($record->get('model')->value === 'test-model', 'Resolved model is stored');
  $check(count($record->get('labels')) === 4, 'Effective classification values are indexed');
  $check(!$manager->enqueue($submission), 'Unchanged completed result is not billed twice');

  foreach ([SettingsForm::class => [], ClassificationResultsForm::class => [$webform], AnalyzeSubmissionsForm::class => [$webform], ReviewAnalysisForm::class => [$webform, $submission]] as $class => $args) {
    $form = \Drupal::formBuilder()->getForm($class, ...$args);
    if ($class === ClassificationResultsForm::class) {
      $check(isset($form['process_pending']) && !isset($form['backfill']), 'Immediate processing button replaces the historical analysis link');
    }
    $html = (string) \Drupal::service('renderer')->renderRoot($form);
    $check(strlen($html) > 100, 'Form builds and renders: ' . basename(str_replace('\\', '/', $class)));
  }
  $handler = $webform->getHandler('classification');
  $handler->setWebform($webform);
  $state = new FormState();
  $handler_form = $handler->buildConfigurationForm([], $state);
  $check(isset($handler_form['question_rows'][0]['criteria']), 'Handler exposes a question builder');


  $rows = [];
  foreach ($questions as $id => $q) {
    $criteria = [];
    foreach ($q['criteria'] ?? [] as $key => $description) {
      $criteria[] = ($q['type'] === 'choice' ? "$key | " : '') . $description;
    }
    $rows[] = ['id' => $id, 'label' => $q['label'], 'type' => $q['type'], 'instructions' => $q['instructions'], 'criteria' => implode("\n", $criteria), 'confidence_threshold' => 0.75, 'threshold' => 0.5, 'review_min' => 0.35, 'review_max' => 0.65];
  }
  $state = new FormState();
  $state->setValues(['question_rows' => $rows, 'fields' => ['message'], 'automatic' => FALSE, 'on_update' => FALSE, 'model' => '']);
  $handler->validateConfigurationForm($handler_form, $state);
  $check(!$state->hasAnyErrors() && count(QuestionDefinition::parse($state->getValue('questions'))) === 4, 'Question builder validates and serializes all three question types');
  $handler->submitConfigurationForm($handler_form, $state);
  $check($handler->getConfiguration()['settings']['fields'] === ['message'], 'Handler saves selected fields and generated questions');

  $context = ['sandbox' => [], 'results' => [], 'finished' => 0];
  AnalyzeSubmissionsForm::queueBatch($fixture, 'missing', '', '', $sid, $context);
  $check($context['results']['queued'] === 0 && $context['finished'] === 1, 'Historical missing-only batch skips completed analyses');
  $batch_submission = WebformSubmission::create(['webform_id' => $fixture, 'data' => ['message' => 'Historical request']]);
  $batch_submission->save();
  $submission_ids[] = (int) $batch_submission->id();
  $context = ['sandbox' => [], 'results' => [], 'finished' => 0];
  AnalyzeSubmissionsForm::queueBatch($fixture, 'missing', '', '', (int) $batch_submission->id(), $context);
  $check($context['results']['queued'] === 1, 'Historical batch queues eligible missing analysis');
  $batch_submission->delete();

  $review = ReviewAnalysisForm::create($container);
  $state = new FormState();
  $form = $review->buildForm([], $state, $webform, $submission);
  $state->setValue('analysis_generation', $form['analysis_generation']['#default_value']);
  $state->setValue('analysis_review_version', $form['analysis_review_version']['#default_value']);
  $state->setValue('corrections', ['intent' => 'refund']);
  $review->submitForm($form, $state);
  $record = $manager->load($sid);
  $check($record->decoded('overrides')['intent'] === 'refund', 'Staff correction is stored separately');
  $check($record->decoded('results')['answers']['intent']['choice'] === 'inquiry', 'Original model answer is preserved');
  $check($record->get('review_status')->value === 'reviewed', 'Review status is saved');
  $state->setValue('corrections', ['intent' => 'complaint']);
  $review->submitForm($form, $state);
  $check($manager->load($sid)->decoded('overrides')['intent'] === 'refund', 'A stale review form cannot overwrite a newer staff review');

  $filter_ids = $entities->getStorage('vactory_webform_analysis')->getQuery()->accessCheck(FALSE)->condition('labels', 'intent:refund')->condition('id', $sid)->execute();
  $check(count($filter_ids) === 1, 'Classification filtering includes staff corrections');

  $access = new AnalysisAccess();
  $check(!$access->access($webform, $submission, new AnonymousUserSession())->isAllowed(), 'Anonymous users cannot access analysis');
  $check($access->access($webform, $submission, User::load(1))->isAllowed(), 'Authorized administrator can access analysis');
  $check($record->access('view', new AnonymousUserSession()) === FALSE, 'Analysis entity access also protects stored results');


  $manager->enqueue($submission, TRUE);
  $fake->duringCall = static function () use ($manager, $submission) {
    $manager->enqueue($submission, TRUE);
  };
  $manager->process($item($manager->load($sid)));
  $check($manager->load($sid)->get('status')->value === 'pending', 'A newer queue generation survives an in-flight successful response');
  $fake->fail = TRUE;
  $manager->process($item($manager->load($sid)));
  $check($manager->load($sid)->get('status')->value === 'pending' && (int) $manager->load($sid)->get('attempts')->value === 0, 'A newer queue generation survives an in-flight provider failure');
  $fake->duringCall = NULL;
  $fake->fail = FALSE;
  $manager->process($item($manager->load($sid)));
  $record = $manager->load($sid);

  $old_item = $item($record);
  $manager->enqueue($submission, TRUE);
  $before = $fake->calls;
  $manager->process($old_item);
  $check($fake->calls === $before, 'Superseded queue generation does not call the provider');
  $manager->process($item($manager->load($sid)));
  $check($manager->load($sid)->decoded('overrides') === [], 'Successful reanalysis clears old corrections');

  $manager->enqueue($submission, TRUE);
  $submission->setData(['message' => 'Changed request']);
  $submission->save();
  $before = $fake->calls;
  $manager->process($item($manager->load($sid)));
  $check($manager->load($sid)->get('status')->value === 'stale' && $fake->calls === $before, 'Input changed before processing is not sent under an old fingerprint');

  $manager->enqueue($submission, TRUE);
  $fake->duringCall = static function () use ($sid) {
    $changed = WebformSubmission::load($sid);
    $changed->setData(['message' => 'Changed during the call']);
    $changed->save();
  };
  $manager->process($item($manager->load($sid)));
  $check($manager->load($sid)->get('status')->value === 'stale', 'Input changed during the call cannot overwrite current classification');
  $fake->duringCall = NULL;

  $submission = WebformSubmission::load($sid);
  $manager->enqueue($submission, TRUE);
  $fake->fail = TRUE;
  $max = (int) \Drupal::config('vactory_webform_typesafe.settings')->get('max_attempts');
  for ($i = 0; $i < $max; $i++) {
    try {
      $manager->process($item($manager->load($sid)));
    }
    catch (DelayedRequeueException) {

    }
  }
  $record = $manager->load($sid);
  $check($record->get('status')->value === 'failed' && (int) $record->get('attempts')->value === $max, 'Provider failures stop after the configured number of attempts');
  $check(!str_contains($record->get('error')->value, 'PRIVATE'), 'Provider errors cannot leak evaluated content');
  $fake->fail = FALSE;

  $submission->set('in_draft', TRUE)->save();
  $check(!$manager->enqueue($submission, TRUE), 'Draft submissions are skipped');
  $submission->set('in_draft', FALSE)->setData(['message' => ''])->save();
  $check(!$manager->enqueue($submission, TRUE), 'Empty inputs are skipped');


  $config = $handler->getConfiguration();
  $config['settings']['automatic'] = TRUE;
  $config['settings']['on_update'] = TRUE;
  $handler->setConfiguration($config);
  $handler->setWebform($webform);
  $webform->updateWebformHandler($handler);
  $webform->save();
  $auto = WebformSubmission::create(['webform_id' => $fixture, 'data' => ['message' => 'Automatically queued request']]);
  $auto->save();
  $auto_sid = (int) $auto->id();
  $submission_ids[] = $auto_sid;
  $auto_record = $manager->load($auto_sid);
  $check($auto_record && $auto_record->get('status')->value === 'pending', 'Handler automatically queues a new completed submission');
  $generation = $auto_record->get('generation')->value;
  $auto->setData(['message' => 'Updated automatic request'])->save();
  $check($manager->load($auto_sid)->get('generation')->value !== $generation, 'Handler queues changed selected fields on update');
  $auto->delete();

  $submission->delete();
  $check(!$manager->load($sid), 'Deleting the submission deletes its analysis');
  print "\n$checks integration checks passed. No TypeSafe requests made.\n";
}
finally {
  foreach ($submission_ids as $sid) {
    if ($submission = WebformSubmission::load($sid)) {
      $submission->delete();
    }
  }
  if ($webform) {
    $webform->delete();
  }
  // Delete only queue items created by this test; leave real work untouched.
  $database = \Drupal::database();
  $queued = $database->select('queue', 'q')->fields('q', ['item_id', 'data'])->condition('name', AnalysisManager::QUEUE)->execute();
  foreach ($queued as $row) {
    $data = unserialize($row->data, ['allowed_classes' => FALSE]);
    if (in_array((int) ($data['sid'] ?? 0), $submission_ids, TRUE)) {
      $database->delete('queue')->condition('item_id', $row->item_id)->execute();
    }
  }
  $switcher->switchBack();
}
