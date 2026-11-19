<?php

namespace Drupal\Tests\vactory_webform_typesafe\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\vactory_webform_typesafe\Form\ReviewAnalysisForm;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

require_once __DIR__ . '/TypeSafeKernelTestBase.php';

/**
 * Characterizes ReviewAnalysisForm::buildForm().
 *
 * @group vactory_webform_typesafe
 */
class ReviewAnalysisFormTest extends TypeSafeKernelTestBase {

  /**
   * The Webform fixture.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The submission fixture.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $submission;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->webform = $this->createTypeSafeWebform();
    $this->submission = $this->createSubmission($this->webform, ['message' => 'Please refund me.']);
  }

  /**
   * Builds the form.
   */
  protected function build(?WebformInterface $webform = NULL, ?WebformSubmissionInterface $submission = NULL, ?FormState $form_state = NULL): array {
    $form_state ??= new FormState();
    $submission ??= $this->submission;
    $form = ReviewAnalysisForm::create($this->container)->buildForm([], $form_state, $webform ?? $this->webform, $submission);
    $this->assertSame((int) $submission->id(), $form_state->get('sid'));
    return $form;
  }

  /**
   * Returns the current fingerprint of the fixture submission.
   */
  protected function fingerprint(): string {
    return $this->manager()->snapshot($this->submission)['fingerprint'];
  }

  /**
   * A submission without analysis shows the empty message only.
   */
  public function testRecordMissing(): void {
    $this->loginWith();
    $form = $this->build();
    $this->assertSame(0, $form['#cache']['max-age']);
    $this->assertSame('This submission has not been analyzed.', (string) $form['empty']['#markup']);
    foreach ([
      'analysis_generation',
      'analysis_review_version',
      'status',
      'outdated',
      'corrections',
      'answers',
      'reviewer',
    ] as $key) {
      $this->assertArrayNotHasKey($key, $form);
    }
    $this->assertSame('actions', $form['actions']['#type']);
    $this->assertFalse($form['actions']['review']['#access']);
    $this->assertSame('Save corrections and mark reviewed', (string) $form['actions']['review']['#value']);
    $this->assertTrue($form['actions']['reanalyze']['#access']);
    $this->assertSame(['::reanalyze'], $form['actions']['reanalyze']['#submit']);
    $this->assertSame([], $form['actions']['reanalyze']['#limit_validation_errors']);
    $this->assertSame('Corrections are stored separately from model output. A successful reanalysis replaces them.', (string) $form['help']['#markup']);
  }

  /**
   * Reanalyze requires the run permission and an enabled handler.
   */
  public function testReanalyzeAccess(): void {
    $this->loginWith([
      'view any webform submission',
      'review vactory webform typesafe',
    ]);
    $this->assertFalse($this->build()['actions']['reanalyze']['#access']);

    $this->loginWith();
    $disabled = $this->createTypeSafeWebform('typesafe_disabled', NULL, [], FALSE);
    $submission = $this->createSubmission($disabled, ['message' => 'x']);
    $this->assertFalse($this->build($disabled, $submission)['actions']['reanalyze']['#access']);
  }

  /**
   * A current completed analysis exposes answers and editable corrections.
   */
  public function testCurrentCompletedAnalysis(): void {
    $this->loginWith();
    $this->createRecord($this->submission, [
      'fingerprint' => $this->fingerprint(),
      'review_status' => 'needs_review',
      'overrides' => json_encode(['intent' => 'complaint', 'urgency' => '2']),
      'attempts' => 2,
      'results' => json_encode([
        'answers' => $this->defaultAnswers() + [
          'unknown' => ['type' => 'noul', 'noul' => 0.9],
        ],
      ]),
    ]);
    $form = $this->build();
    $record = $this->manager()->load((int) $this->submission->id());

    $this->assertSame('hidden', $form['analysis_generation']['#type']);
    $this->assertSame('gen-' . $this->submission->id(), $form['analysis_generation']['#default_value']);
    $this->assertSame('hidden', $form['analysis_review_version']['#type']);
    $this->assertSame(hash('sha256', json_encode([
      $record->get('overrides')->value,
      $record->get('reviewer')->value,
      $record->get('reviewed')->value,
      $record->get('review_status')->value,
    ])), $form['analysis_review_version']['#default_value']);
    $this->assertSame('Status: completed. Review: needs_review. Model: test-model. Attempts: 2.', (string) $form['status']['#plain_text']);
    $this->assertArrayNotHasKey('error', $form);
    $this->assertArrayNotHasKey('outdated', $form);
    $this->assertArrayNotHasKey('reviewer', $form);
    $this->assertArrayNotHasKey('empty', $form);
    $this->assertTrue($form['actions']['review']['#access']);
    $this->assertTrue($form['actions']['reanalyze']['#access']);

    $this->assertSame(['intent', 'sentiment', 'urgency', 'callback'], array_keys($form['answers']));
    $this->assertSame(['#tree', 'intent', 'sentiment', 'urgency', 'callback'], array_keys($form['corrections']));
    $this->assertTrue($form['corrections']['#tree']);

    // Choice question.
    $intent = $form['answers']['intent'];
    $this->assertSame('details', $intent['#type']);
    $this->assertSame('Objet principal de la demande', $intent['#title']);
    $this->assertTrue($intent['#open']);
    $this->assertSame('Model result: Demande de remboursement', (string) $intent['value']['#plain_text']);
    $this->assertSame('Confidence: 90%', (string) $intent['confidence']['#plain_text']);
    $this->assertArrayNotHasKey('probability', $intent);
    $this->assertSame('table', $intent['probabilities']['#type']);
    $this->assertSame(['Category or level', 'Probability'], array_map('strval', $intent['probabilities']['#header']));
    $this->assertSame([
      [['data' => ['#plain_text' => 'Demande de remboursement']], '90%'],
      [['data' => ['#plain_text' => 'Réclamation']], '10%'],
    ], $intent['probabilities']['#rows']);
    $correction = $form['corrections']['intent'];
    $this->assertSame('select', $correction['#type']);
    $this->assertSame('Objet principal de la demande — staff correction', (string) $correction['#title']);
    $this->assertSame('complaint', $correction['#default_value']);
    $this->assertFalse($correction['#disabled']);
    $this->assertSame([
      '' => 'Keep model result',
      'inquiry' => 'Demande de renseignements',
      'complaint' => 'Réclamation',
      'quotation' => 'Demande de devis',
      'support' => 'Demande d’assistance',
      'refund' => 'Demande de remboursement',
      'other' => 'Autre demande',
      'unclear' => 'Informations insuffisantes',
    ], array_map('strval', $correction['#options']));

    // A second choice question without override.
    $this->assertSame('Confidence: 60%', (string) $form['answers']['sentiment']['confidence']['#plain_text']);
    $this->assertSame('', $form['corrections']['sentiment']['#default_value']);

    // Score question.
    $urgency = $form['answers']['urgency'];
    $this->assertSame('Model result: 1.5', (string) $urgency['value']['#plain_text']);
    $this->assertSame('Confidence: 80%', (string) $urgency['confidence']['#plain_text']);
    $levels = [
      '0 — Faible : aucun délai ni impact immédiat',
      '1 — Normal : demande courante',
      '2 — Élevé : échéance proche explicitement mentionnée ou perturbation importante en cours',
    ];
    $this->assertSame([
      [['data' => ['#plain_text' => $levels[0]]], '10%'],
      [['data' => ['#plain_text' => $levels[1]]], '30%'],
      [['data' => ['#plain_text' => $levels[2]]], '60%'],
    ], $urgency['probabilities']['#rows']);
    $correction = $form['corrections']['urgency'];
    $this->assertSame('number', $correction['#type']);
    $this->assertSame(0, $correction['#min']);
    $this->assertSame(2, $correction['#max']);
    $this->assertSame('any', $correction['#step']);
    $this->assertSame('2', $correction['#default_value']);
    $this->assertSame('Leave empty to retain the model result.', (string) $correction['#description']);
    $this->assertArrayNotHasKey('#options', $correction);

    // Noul question.
    $callback = $form['answers']['callback'];
    $this->assertSame('Model result: Oui', (string) $callback['value']['#plain_text']);
    $this->assertArrayNotHasKey('confidence', $callback);
    $this->assertArrayNotHasKey('probabilities', $callback);
    $this->assertSame('Probability that the statement is true: 72.5%', (string) $callback['probability']['#plain_text']);
    $correction = $form['corrections']['callback'];
    $this->assertSame('select', $correction['#type']);
    $this->assertSame(['' => 'Keep model result', 'yes' => 'Oui', 'no' => 'Non'], array_map('strval', $correction['#options']));
    $this->assertSame('', $correction['#default_value']);
  }

  /**
   * Noul answers below the threshold and rounding of probabilities.
   */
  public function testNoulNoAndRounding(): void {
    $this->loginWith();
    $answers = $this->defaultAnswers();
    $answers['callback']['noul'] = 0.12345;
    $answers['intent']['confidence'] = 0.87654;
    $this->createRecord($this->submission, [
      'fingerprint' => $this->fingerprint(),
      'results' => json_encode(['answers' => $answers]),
    ]);
    $form = $this->build();
    $this->assertSame('Model result: Non', (string) $form['answers']['callback']['value']['#plain_text']);
    $this->assertSame('Probability that the statement is true: 12.3%', (string) $form['answers']['callback']['probability']['#plain_text']);
    $this->assertSame('Confidence: 87.7%', (string) $form['answers']['intent']['confidence']['#plain_text']);
  }

  /**
   * An outdated analysis is shown but cannot be reviewed.
   */
  public function testOutdatedAnalysis(): void {
    $this->loginWith();
    $this->createRecord($this->submission, [
      'fingerprint' => 'not-the-current-one',
      'error' => 'Something failed before.',
    ]);
    $form = $this->build();
    $this->assertSame('This analysis is outdated or its handler is disabled. Reanalyze before reviewing.', (string) $form['outdated']['#markup']);
    $this->assertSame('Something failed before.', $form['error']['#plain_text']);
    $this->assertFalse($form['actions']['review']['#access']);
    $this->assertSame(['intent', 'sentiment', 'urgency', 'callback'], array_keys($form['answers']));
    foreach (['intent', 'sentiment', 'urgency', 'callback'] as $id) {
      $this->assertTrue($form['corrections'][$id]['#disabled'], $id);
    }
  }

  /**
   * A disabled handler makes every analysis outdated.
   */
  public function testDisabledHandlerIsOutdated(): void {
    $this->loginWith();
    // Same input and settings produce the same fingerprint on any Webform.
    $fingerprint = $this->fingerprint();
    $disabled = $this->createTypeSafeWebform('typesafe_disabled', NULL, [], FALSE);
    $submission = $this->createSubmission($disabled, ['message' => 'Please refund me.']);
    $this->createRecord($submission, ['fingerprint' => $fingerprint]);
    $form = $this->build($disabled, $submission);
    $this->assertArrayHasKey('outdated', $form);
    $this->assertFalse($form['actions']['review']['#access']);
    $this->assertFalse($form['actions']['reanalyze']['#access']);
  }

  /**
   * A snapshot exception (oversized input) is treated as outdated.
   */
  public function testSnapshotFailureIsOutdated(): void {
    $this->loginWith();
    $this->createRecord($this->submission, ['fingerprint' => $this->fingerprint()]);
    $this->config('vactory_webform_typesafe.settings')->set('max_input_bytes', 5)->save();
    $form = $this->build();
    $this->assertArrayHasKey('outdated', $form);
    $this->assertFalse($form['actions']['review']['#access']);
  }

  /**
   * Non-completed analyses hide previous answers.
   */
  public function testPendingAnalysis(): void {
    $this->loginWith();
    $this->createRecord($this->submission, [
      'fingerprint' => $this->fingerprint(),
      'status' => 'pending',
    ]);
    $form = $this->build();
    $this->assertSame(['#tree' => TRUE], $form['corrections']);
    $this->assertArrayNotHasKey('answers', $form);
    $this->assertArrayNotHasKey('outdated', $form);
    $this->assertFalse($form['actions']['review']['#access']);
    $this->assertSame('Status: pending. Review: unreviewed. Model: test-model. Attempts: 1.', (string) $form['status']['#plain_text']);
  }

  /**
   * Without the review permission corrections are disabled.
   */
  public function testWithoutReviewPermission(): void {
    $this->loginWith([
      'view any webform submission',
      'run vactory webform typesafe',
    ]);
    $this->createRecord($this->submission, ['fingerprint' => $this->fingerprint()]);
    $form = $this->build();
    $this->assertArrayNotHasKey('outdated', $form);
    $this->assertFalse($form['actions']['review']['#access']);
    $this->assertTrue($form['actions']['reanalyze']['#access']);
    $this->assertTrue($form['corrections']['intent']['#disabled']);
  }

  /**
   * A reviewed analysis reports reviewer and UTC time.
   */
  public function testReviewedAnalysis(): void {
    $this->loginWith();
    $this->createRecord($this->submission, [
      'fingerprint' => $this->fingerprint(),
      'review_status' => 'reviewed',
      'reviewer' => 7,
      'reviewed' => gmmktime(3, 4, 5, 1, 2, 2024),
    ]);
    $form = $this->build();
    $this->assertSame('Reviewed by user 7 at 2024-01-02 03:04:05 UTC.', (string) $form['reviewer']['#plain_text']);
    $this->assertTrue($form['actions']['review']['#access']);
  }

}
