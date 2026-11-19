<?php

namespace Drupal\Tests\vactory_webform_typesafe\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\vactory_webform_typesafe\Batch\ProcessPendingBatch;
use Drupal\vactory_webform_typesafe\Form\AnalyzeSubmissionsForm;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

require_once __DIR__ . '/TypeSafeKernelTestBase.php';

/**
 * Characterizes historical queueing and batch completion messages.
 *
 * @group vactory_webform_typesafe
 */
class AnalyzeSubmissionsFormTest extends TypeSafeKernelTestBase {

  /**
   * The Webform fixture.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * Runs one batch iteration.
   */
  protected function queue(string $mode, int $max, string $from = '', string $to = '', ?array $context = NULL): array {
    $context ??= ['sandbox' => [], 'results' => []];
    AnalyzeSubmissionsForm::queueBatch($this->webform->id(), $mode, $from, $to, $max, $context);
    return $context;
  }

  /**
   * Creates the mixed fixture used by the mode tests.
   *
   * @return int[]
   *   Submission IDs keyed by fixture name.
   */
  protected function createMixedFixture(): array {
    $this->loginWith();
    $this->webform = $this->createTypeSafeWebform();
    $sids = [];
    // Name => [data, record status or NULL, fingerprint matches, draft].
    $fixtures = [
      'missing' => [['message' => 'No analysis yet'], NULL, FALSE, FALSE],
      'failed' => [['message' => 'Failed'], 'failed', FALSE, FALSE],
      'current' => [['message' => 'Current'], 'completed', TRUE, FALSE],
      'changed' => [['message' => 'Changed'], 'completed', FALSE, FALSE],
      'stale' => [['message' => 'Stale'], 'stale', TRUE, FALSE],
      'draft' => [['message' => 'Draft'], NULL, FALSE, TRUE],
      'empty' => [['secret' => 'not selected'], NULL, FALSE, FALSE],
      'pending' => [['message' => 'Pending'], 'pending', TRUE, FALSE],
    ];
    foreach ($fixtures as $name => [$data, $status, $match, $draft]) {
      $values = $draft ? ['in_draft' => TRUE] : [];
      $submission = $this->createSubmission($this->webform, $data, $values);
      if ($status !== NULL) {
        $record = ['status' => $status];
        if ($match) {
          $snapshot = $this->manager()->snapshot($submission);
          $record['fingerprint'] = $snapshot['fingerprint'];
        }
        $this->createRecord($submission, $record);
      }
      $sids[$name] = (int) $submission->id();
    }
    return $sids;
  }

  /**
   * Each mode selects a specific set of completed submissions.
   *
   * @dataProvider providerModes
   */
  public function testModes(string $mode, array $queued, int $skipped): void {
    $sids = $this->createMixedFixture();
    $context = $this->queue($mode, max($sids));
    $this->assertSame([
      'queued' => count($queued),
      'skipped' => $skipped,
      'failed' => 0,
    ], $context['results']);
    $this->assertSame(array_map(static fn($name) => $sids[$name], $queued), $this->queuedSids());
    $this->assertSame(1, $context['finished']);
    $this->assertSame($sids['pending'], $context['sandbox']['cursor']);
    $this->assertSame(sprintf('Queued %d; skipped %d; failed 0.', count($queued), $skipped), (string) $context['message']);
    foreach ($queued as $name) {
      $record = $this->manager()->load($sids[$name]);
      $this->assertSame('pending', $record->get('status')->value, $name);
      $this->assertSame('unreviewed', $record->get('review_status')->value, $name);
    }
    $this->assertNull($this->manager()->load($sids['draft']));
    $this->assertSame(0, $this->classifier->calls);
  }

  /**
   * Provides modes with the expected queued fixtures and skipped count.
   */
  public static function providerModes(): array {
    return [
      'missing' => ['missing', ['missing'], 6],
      'failed' => ['failed', ['failed'], 6],
      // A failed record whose fingerprint differs is also outdated.
      'outdated' => ['outdated', ['failed', 'changed', 'stale'], 4],
      'all' => [
        'all',
        ['missing', 'failed', 'current', 'changed', 'stale', 'pending'],
        1,
      ],
      'unknown mode' => ['bogus', [], 7],
    ];
  }

  /**
   * Submissions above the max ID or at or below the cursor are ignored.
   */
  public function testMaxAndCursor(): void {
    $sids = $this->createMixedFixture();
    $context = $this->queue('all', $sids['current']);
    $this->assertSame(['queued' => 3, 'skipped' => 0, 'failed' => 0], $context['results']);
    $this->assertSame([$sids['missing'], $sids['failed'], $sids['current']], $this->queuedSids());
    $this->assertSame($sids['current'], $context['sandbox']['cursor']);

    $context = $this->queue('all', max($sids), '', '', [
      'sandbox' => ['cursor' => $sids['stale']],
      'results' => ['queued' => 10, 'skipped' => 1, 'failed' => 2],
    ]);
    // Results accumulate across iterations.
    $this->assertSame(['queued' => 11, 'skipped' => 2, 'failed' => 2], $context['results']);
    $this->assertSame([
      $sids['missing'],
      $sids['failed'],
      $sids['current'],
      $sids['pending'],
    ], $this->queuedSids());
    $this->assertSame('Queued 11; skipped 2; failed 2.', (string) $context['message']);
  }

  /**
   * The date range is inclusive and interpreted in UTC.
   *
   * @dataProvider providerDates
   */
  public function testDateRange(string $from, string $to, array $expected): void {
    $this->loginWith();
    $this->webform = $this->createTypeSafeWebform();
    $sids = [];
    foreach ([
      'before' => '2024-01-09 23:59:59',
      'start' => '2024-01-10 00:00:00',
      'end' => '2024-01-11 23:59:59',
      'after' => '2024-01-12 00:00:00',
    ] as $name => $date) {
      $submission = $this->createSubmission($this->webform, ['message' => $name], ['created' => strtotime($date . ' UTC')]);
      $sids[$name] = (int) $submission->id();
    }
    $this->assertSame(strtotime('2024-01-10 00:00:00 UTC'), (int) $this->container->get('entity_type.manager')->getStorage('webform_submission')->load($sids['start'])->get('created')->value);
    $context = $this->queue('missing', max($sids), $from, $to);
    $this->assertSame(array_map(static fn($name) => $sids[$name], $expected), $this->queuedSids());
    $this->assertSame(count($expected), $context['results']['queued']);
  }

  /**
   * Provides date ranges.
   */
  public static function providerDates(): array {
    return [
      'no range' => ['', '', ['before', 'start', 'end', 'after']],
      'both bounds' => ['2024-01-10', '2024-01-11', ['start', 'end']],
      'from only' => ['2024-01-10', '', ['start', 'end', 'after']],
      'to only' => ['', '2024-01-11', ['before', 'start', 'end']],
      'single day' => ['2024-01-12', '2024-01-12', ['after']],
    ];
  }

  /**
   * Iterations process 50 submissions and report progress against max.
   */
  public function testFiftyPerIteration(): void {
    $this->loginWith();
    $this->webform = $this->createTypeSafeWebform();
    $sids = [];
    for ($i = 0; $i < 51; $i++) {
      $sids[] = (int) $this->createSubmission($this->webform, ['message' => 'm' . $i])->id();
    }
    $max = max($sids);
    $context = $this->queue('failed', $max);
    $this->assertSame(['queued' => 0, 'skipped' => 50, 'failed' => 0], $context['results']);
    $this->assertSame($sids[49], $context['sandbox']['cursor']);
    $this->assertEqualsWithDelta(min(0.99, $sids[49] / $max), $context['finished'], 0.0000001);
    $this->assertLessThan(1, $context['finished']);

    $context = $this->queue('failed', $max, '', '', $context);
    $this->assertSame(['queued' => 0, 'skipped' => 51, 'failed' => 0], $context['results']);
    $this->assertSame($max, $context['sandbox']['cursor']);
    $this->assertSame(1, $context['finished']);
  }

  /**
   * Exceptions while selecting or queueing count as failed.
   */
  public function testFailures(): void {
    $sids = $this->createMixedFixture();
    $this->config('vactory_webform_typesafe.settings')->set('max_input_bytes', 10)->save();
    $context = $this->queue('missing', max($sids));
    // Only "missing" reaches enqueue(); its oversized input throws.
    $this->assertSame(['queued' => 0, 'skipped' => 6, 'failed' => 1], $context['results']);
    $context = $this->queue('outdated', max($sids));
    // Records whose snapshot is computed throw too; "empty" has no record.
    $this->assertSame(['queued' => 0, 'skipped' => 2, 'failed' => 5], $context['results']);
    $this->assertSame('Queued 0; skipped 2; failed 5.', (string) $context['message']);
    $this->assertSame([], $this->queuedSids());
  }

  /**
   * Access is rechecked at each iteration.
   *
   * @dataProvider providerAccess
   */
  public function testAccessDenied(array $permissions, string $wid): void {
    $this->loginWith(self::ALL_PERMISSIONS);
    $this->webform = $this->createTypeSafeWebform();
    $sid = (int) $this->createSubmission($this->webform, ['message' => 'x'])->id();
    $this->loginWith($permissions);
    $context = ['sandbox' => [], 'results' => []];
    try {
      AnalyzeSubmissionsForm::queueBatch($wid, 'all', '', '', $sid, $context);
      $this->fail('Access was not denied.');
    }
    catch (AccessDeniedHttpException) {
      $this->assertSame([], $this->queuedSids());
    }
  }

  /**
   * Provides denied cases.
   */
  public static function providerAccess(): array {
    return [
      'no run permission' => [['view any webform submission'], 'typesafe_test'],
      'no submission access' => [
        ['run vactory webform typesafe'],
        'typesafe_test',
      ],
      'unknown webform' => [self::ALL_PERMISSIONS, 'missing_webform'],
    ];
  }

  /**
   * The form passes the latest submission ID as max and redirects.
   */
  public function testBuildValidateAndSubmit(): void {
    require_once $this->root . '/core/includes/form.inc';
    $sids = $this->createMixedFixture();
    $form_object = AnalyzeSubmissionsForm::create($this->container);
    $form_state = new FormState();
    $form = $form_object->buildForm([], $form_state, $this->webform);
    $this->assertSame('typesafe_test', $form_state->get('webform_id'));
    $this->assertStringStartsWith('This operation queues matching completed submissions', (string) $form['help']['#markup']);
    $this->assertSame(['missing', 'failed', 'outdated', 'all'], array_keys($form['mode']['#options']));
    $this->assertSame('missing', $form['mode']['#default_value']);
    $this->assertSame('date', $form['from']['#type']);
    $this->assertSame('date', $form['to']['#type']);
    $this->assertFalse($form['actions']['submit']['#disabled']);

    $form_state->setValues([
      'mode' => 'outdated',
      'from' => '2024-02-01',
      'to' => '2024-01-01',
    ]);
    $form_object->validateForm($form, $form_state);
    $this->assertSame(['to' => 'The end date must be on or after the start date.'], array_map('strval', $form_state->getErrors()));
    $form_state->clearErrors();
    $form_state->setValues([
      'mode' => 'outdated',
      'from' => '2024-01-01',
      'to' => '2024-01-01',
    ]);
    $form_object->validateForm($form, $form_state);
    $this->assertSame([], $form_state->getErrors());

    $form_object->submitForm($form, $form_state);
    $batch = &batch_get();
    $set = end($batch['sets']);
    $this->assertSame([[
      [AnalyzeSubmissionsForm::class, 'queueBatch'],
      ['typesafe_test', 'outdated', '2024-01-01', '2024-01-01', max($sids)],
    ],
    ], $set['operations']);
    $this->assertSame([AnalyzeSubmissionsForm::class, 'finished'], $set['finished']);
    $this->assertSame('Queueing submissions', (string) $set['title']);
    $redirect = $form_state->getRedirect();
    $this->assertSame('vactory_webform_typesafe.results', $redirect->getRouteName());
    $this->assertSame(['webform' => 'typesafe_test'], $redirect->getRouteParameters());
    $batch = [];
  }

  /**
   * Without an enabled handler the submit button is disabled.
   */
  public function testBuildWithoutHandler(): void {
    $this->loginWith();
    $webform = $this->createTypeSafeWebform('typesafe_disabled', NULL, [], FALSE);
    $form = AnalyzeSubmissionsForm::create($this->container)->buildForm([], new FormState(), $webform);
    $this->assertSame('Add and enable a TypeSafe classification handler for this Webform first.', (string) $form['help']['#markup']);
    $this->assertTrue($form['actions']['submit']['#disabled']);
  }

  /**
   * Batch completion messages for historical queueing.
   */
  public function testFinished(): void {
    $results = ['queued' => 3, 'skipped' => 2, 'failed' => 1];
    AnalyzeSubmissionsForm::finished(TRUE, $results, []);
    AnalyzeSubmissionsForm::finished(TRUE, [], []);
    AnalyzeSubmissionsForm::finished(FALSE, ['queued' => 3], []);
    $this->assertSame([
      'Queued 3 submissions. Skipped 2; failed 1. Run cron to process queued analyses.',
      'Queued 0 submissions. Skipped 0; failed 0. Run cron to process queued analyses.',
    ], $this->messages('status'));
    $this->assertSame(['Queueing stopped. Already queued submissions can still be processed.'], $this->messages('error'));
  }

  /**
   * Batch completion messages for immediate pending processing.
   */
  public function testProcessPendingFinished(): void {
    $results = ['completed' => 4, 'pending' => 2, 'other' => 1];
    ProcessPendingBatch::finished(TRUE, $results, []);
    ProcessPendingBatch::finished(TRUE, [], []);
    ProcessPendingBatch::finished(FALSE, ['completed' => 4], []);
    $this->assertSame([
      'Terminées : 4. En attente de cron ou d’une nouvelle tentative : 2. En échec ou obsolètes : 1. Actualisez les résultats pour consulter leur état actuel.',
      'Terminées : 0. En attente de cron ou d’une nouvelle tentative : 0. En échec ou obsolètes : 0. Actualisez les résultats pour consulter leur état actuel.',
    ], $this->messages('status'));
    $this->assertSame(['Le traitement a été interrompu. Cron pourra traiter les soumissions encore en attente.'], $this->messages('error'));
  }

}
