<?php

namespace Drupal\Tests\vactory_webform_typesafe\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\vactory_webform_typesafe\Exception\JevApiException;
use Drupal\vactory_webform_typesafe\Service\AnalysisManager;
use Drupal\vactory_webform_typesafe\Service\JevResponseValidator;
use Drupal\vactory_webform_typesafe\Service\TypeSafeClassifierInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Characterizes AnalysisManager enqueue/process/prepare before refactoring.
 *
 * @coversDefaultClass \Drupal\vactory_webform_typesafe\Service\AnalysisManager
 * @group vactory_webform_typesafe
 */
class AnalysisManagerTest extends KernelTestBase {

  private const NOW = 1700000000;
  private const SAVE = 'vactory_webform_typesafe:save:';
  private const WORK = 'vactory_webform_typesafe:work:';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'path',
    'path_alias',
    'field',
    'webform',
    'vactory_webform_typesafe',
  ];

  /**
   * The inspectable lock backend.
   *
   * @var object
   */
  protected $lock;

  /**
   * The mocked classifier.
   *
   * @var \Drupal\vactory_webform_typesafe\Service\TypeSafeClassifierInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $classifier;

  /**
   * The manager under test.
   *
   * @var \Drupal\vactory_webform_typesafe\Service\AnalysisManager
   */
  protected $manager;

  /**
   * The test webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('user');
    $this->installSchema('webform', ['webform']);
    $this->installConfig(['webform', 'vactory_webform_typesafe']);
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('vactory_webform_analysis');

    $this->lock = self::createLock();
    $this->classifier = $this->createMock(TypeSafeClassifierInterface::class);
    $this->manager = $this->createManager();

    $this->webform = Webform::create([
      'id' => 'typesafe_test',
      'title' => 'Test',
      'status' => 'open',
    ]);
    $this->webform->setElements([
      'message' => ['#type' => 'textarea', '#title' => 'Message'],
      'secret' => ['#type' => 'password', '#title' => 'Secret'],
    ]);
    $this->webform->save();
    $handler = $this->container->get('plugin.manager.webform.handler')->createInstance('vactory_typesafe', [
      'handler_id' => 'classification',
      'status' => TRUE,
      'settings' => [
        'fields' => ['message'],
        'automatic' => FALSE,
        'on_update' => FALSE,
        'model' => '',
        'questions' => json_encode(self::questions()),
      ],
    ]);
    $handler->setWebform($this->webform);
    $this->webform->addWebformHandler($handler);
    $this->webform->save();
  }

  /**
   * Builds an in-memory lock that records every call.
   */
  private static function createLock(): LockBackendInterface {
    return new class() implements LockBackendInterface {

      /**
       * Calls: [acquire, name, timeout, result] or [release, name].
       *
       * @var array
       */
      public array $log = [];

      /**
       * Names currently held by this request.
       *
       * @var array
       */
      public array $held = [];

      /**
       * Names held by "another request": acquire fails.
       *
       * @var array
       */
      public array $busy = [];

      /**
       * {@inheritdoc}
       */
      public function acquire($name, $timeout = 30.0) {
        $result = empty($this->busy[$name]);
        $this->log[] = ['acquire', $name, $timeout, $result];
        if ($result) {
          $this->held[$name] = TRUE;
        }
        return $result;
      }

      /**
       * {@inheritdoc}
       */
      public function lockMayBeAvailable($name) {
        return empty($this->busy[$name]) && empty($this->held[$name]);
      }

      /**
       * {@inheritdoc}
       */
      public function wait($name, $delay = 30) {
        return FALSE;
      }

      /**
       * {@inheritdoc}
       */
      public function release($name) {
        $this->log[] = ['release', $name];
        unset($this->held[$name]);
      }

      /**
       * {@inheritdoc}
       */
      public function releaseAll($lock_id = NULL) {
        $this->held = [];
      }

      /**
       * {@inheritdoc}
       */
      public function getLockId() {
        return 'test';
      }

    };
  }

  /**
   * Builds a manager with the test lock, classifier and a fixed clock.
   */
  private function createManager(?QueueFactory $queues = NULL): AnalysisManager {
    $time = $this->createMock(TimeInterface::class);
    $time->method('getCurrentTime')->willReturn(self::NOW);
    return new AnalysisManager(
      $this->container->get('entity_type.manager'),
      $queues ?? $this->container->get('queue'),
      $this->container->get('config.factory'),
      $this->lock,
      $this->container->get('vactory_webform_typesafe.input'),
      $this->classifier,
      $time,
    );
  }

  /**
   * One question of each type.
   */
  private static function questions(): array {
    return [
      'intent' => [
        'label' => 'Intent',
        'type' => 'choice',
        'instructions' => 'Pick one.',
        'criteria' => ['a' => 'A', 'b' => 'B'],
        'confidence_threshold' => 0.75,
      ],
      'urgency' => [
        'label' => 'Urgency',
        'type' => 'score',
        'instructions' => 'Rate it.',
        'criteria' => ['Low', 'Normal', 'High'],
      ],
      'callback' => [
        'label' => 'Callback',
        'type' => 'noul',
        'instructions' => 'Asks for a call.',
      ],
    ];
  }

  /**
   * A validated classifier result.
   */
  private static function result(float $confidence = 0.9, float $noul = 0.8): array {
    return JevResponseValidator::validate([
      'model' => 'jev-test',
      'answers' => [
        'intent' => [
          'type' => 'choice',
          'choice' => 'a',
          'confidence' => $confidence,
          'probabilities' => ['a' => 0.9, 'b' => 0.1],
        ],
        'urgency' => [
          'type' => 'score',
          'score' => 1.2,
          'confidence' => 0.9,
          'probabilities' => [0.0, 0.8, 0.2],
          'legend' => ['Low', 'Normal', 'High'],
        ],
        'callback' => ['type' => 'noul', 'noul' => $noul],
      ],
      'usage' => ['input_tokens' => 1, 'output_tokens' => 2],
    ], self::questions());
  }

  /**
   * Creates and saves a submission.
   */
  private function submission(string $message = 'Please refund', bool $draft = FALSE): WebformSubmissionInterface {
    $submission = WebformSubmission::create([
      'webform_id' => 'typesafe_test',
      'data' => ['message' => $message, 'secret' => 'hidden'],
      'in_draft' => $draft,
    ]);
    $submission->save();
    return $submission;
  }

  /**
   * Enqueues a submission and resets the lock log.
   */
  private function enqueued(string $message = 'Please refund'): WebformSubmissionInterface {
    $submission = $this->submission($message);
    $this->assertTrue($this->manager->enqueue($submission));
    $this->lock->log = [];
    return $submission;
  }

  /**
   * Returns the queue item for the current record.
   */
  private function item(int $sid): array {
    $generation = $this->manager->load($sid)->get('generation')->value;
    return ['sid' => $sid, 'generation' => $generation];
  }

  /**
   * Updates fields of the stored record.
   */
  private function setRecord(int $sid, array $values): void {
    $record = $this->manager->load($sid);
    foreach ($values as $field => $value) {
      $record->set($field, $value);
    }
    $record->save();
  }

  /**
   * Returns a record field value.
   */
  private function field(int $sid, string $field) {
    return $this->manager->load($sid)->get($field)->value;
  }

  /**
   * Deletes a submission without its delete hooks (no analysis cleanup).
   */
  private function rawDeleteSubmission(int $sid): void {
    $database = $this->container->get('database');
    $database->delete('webform_submission_data')->condition('sid', $sid)->execute();
    $database->delete('webform_submission')->condition('sid', $sid)->execute();
    $this->container->get('entity_type.manager')->getStorage('webform_submission')->resetCache([$sid]);
  }

  /**
   * Toggles the handler.
   */
  private function setHandlerStatus(bool $status): void {
    $webform = Webform::load('typesafe_test');
    $webform->getHandler('classification')->setStatus($status);
    $webform->save();
  }

  /**
   * Asserts no lock is held and each successful acquire was released.
   */
  private function assertLocksFree(): void {
    $this->assertSame([], $this->lock->held);
    $open = [];
    foreach ($this->lock->log as $entry) {
      if ($entry[0] === 'acquire' && $entry[3]) {
        $open[$entry[1]] = ($open[$entry[1]] ?? 0) + 1;
      }
      elseif ($entry[0] === 'release') {
        $open[$entry[1]] = ($open[$entry[1]] ?? 0) - 1;
      }
    }
    $this->assertSame([], array_filter($open), 'Every acquired lock is released exactly once.');
  }

  /**
   * Runs process() and returns the DelayedRequeueException delay or NULL.
   */
  private function processDelay(array $item): ?int {
    try {
      $this->manager->process($item);
    }
    catch (DelayedRequeueException $e) {
      return $e->getDelay();
    }
    return NULL;
  }

  /**
   * Returns the number of queue items.
   */
  private function queueCount(): int {
    return $this->container->get('queue')->get(AnalysisManager::QUEUE)->numberOfItems();
  }

  /**
   * Enqueue creates a pending record and one queue item under the save lock.
   *
   * @covers ::enqueue
   * @covers ::snapshot
   */
  public function testEnqueueCreatesPendingRecord(): void {
    $submission = $this->submission();
    $sid = (int) $submission->id();
    $this->assertTrue($this->manager->enqueue($submission));
    $this->assertSame([
      ['acquire', self::SAVE . $sid, 30, TRUE],
      ['release', self::SAVE . $sid],
    ], $this->lock->log);
    $this->assertLocksFree();

    $record = $this->manager->load($sid);
    $state = ['message' => ['label' => 'Message', 'value' => 'Please refund']];
    $snapshot = [$state, self::questions(), 'jev-latest'];
    $fingerprint = hash('sha256', json_encode($snapshot));
    $this->assertSame((string) $sid, (string) $record->id());
    $this->assertSame((string) $sid, (string) $record->get('submission')->target_id);
    $this->assertSame('typesafe_test', $record->get('webform')->value);
    $this->assertSame($fingerprint, $record->get('fingerprint')->value);
    $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $record->get('generation')->value);
    $this->assertSame('jev-latest', $record->get('model')->value);
    $this->assertSame(json_encode(self::questions()), $record->get('questions')->value);
    $this->assertSame('pending', $record->get('status')->value);
    $this->assertSame('unreviewed', $record->get('review_status')->value);
    $this->assertEquals(0, $record->get('attempts')->value);
    // The '' written by enqueue() is an empty item: it reloads as NULL.
    $this->assertNull($record->get('error')->value);
    $this->assertEquals(self::NOW, $record->get('changed')->value);
    $this->assertSame([], $record->get('labels')->getValue());
    $this->assertNull($record->get('results')->value);

    $queue = $this->container->get('queue')->get(AnalysisManager::QUEUE);
    $this->assertSame(1, $queue->numberOfItems());
    $generation = $record->get('generation')->value;
    $expected = ['sid' => $sid, 'generation' => $generation];
    $this->assertSame($expected, $queue->claimItem()->data);
  }

  /**
   * A handler model overrides the default, trimmed.
   *
   * @covers ::enqueue
   * @covers ::snapshot
   */
  public function testEnqueueUsesHandlerModel(): void {
    $webform = Webform::load('typesafe_test');
    $handler = $webform->getHandler('classification');
    $config = $handler->getConfiguration();
    $config['settings']['model'] = ' custom ';
    $handler->setConfiguration($config);
    $webform->save();
    $submission = $this->submission();
    $this->assertTrue($this->manager->enqueue($submission));
    $this->assertSame('custom', $this->field((int) $submission->id(), 'model'));
  }

  /**
   * An unsaved submission is refused before any lock.
   *
   * @covers ::enqueue
   */
  public function testEnqueueUnsaved(): void {
    $submission = WebformSubmission::create([
      'webform_id' => 'typesafe_test',
      'data' => ['message' => 'x'],
    ]);
    $this->assertFalse($this->manager->enqueue($submission));
    $this->assertSame([], $this->lock->log);
  }

  /**
   * A busy save lock refuses without release or writes.
   *
   * @covers ::enqueue
   */
  public function testEnqueueLockBusy(): void {
    $submission = $this->submission();
    $sid = (int) $submission->id();
    $this->lock->busy[self::SAVE . $sid] = TRUE;
    $this->assertFalse($this->manager->enqueue($submission));
    $this->assertSame([['acquire', self::SAVE . $sid, 30, FALSE]], $this->lock->log);
    $this->assertNull($this->manager->load($sid));
    $this->assertSame(0, $this->queueCount());
  }

  /**
   * Draft, empty input and a disabled handler are skipped; lock released.
   *
   * @covers ::enqueue
   * @covers ::snapshot
   */
  public function testEnqueueSkipsIneligible(): void {
    $cases = [
      'draft' => fn() => $this->submission('Hello', TRUE),
      'empty' => fn() => $this->submission(' '),
      'disabled handler' => function () {
        $this->setHandlerStatus(FALSE);
        return $this->submission();
      },
    ];
    foreach ($cases as $label => $factory) {
      $this->lock->log = [];
      $submission = $factory();
      $sid = (int) $submission->id();
      $this->assertFalse($this->manager->enqueue($submission), $label);
      $this->assertSame([
        ['acquire', self::SAVE . $sid, 30, TRUE],
        ['release', self::SAVE . $sid],
      ], $this->lock->log, $label);
      $this->assertNull($this->manager->load($sid), $label);
    }
    $this->assertSame(0, $this->queueCount());
  }

  /**
   * Snapshot exceptions escape enqueue but the lock is released.
   *
   * @covers ::enqueue
   */
  public function testEnqueueSnapshotExceptionReleasesLock(): void {
    $submission = $this->submission();
    $this->config('vactory_webform_typesafe.settings')->set('max_input_bytes', 5)->save();
    try {
      $this->manager->enqueue($submission);
      $this->fail('Expected LengthException.');
    }
    catch (\LengthException $e) {
      $this->assertSame('Selected input exceeds the configured byte limit.', $e->getMessage());
    }
    $this->assertLocksFree();
    $this->assertNull($this->manager->load((int) $submission->id()));
  }

  /**
   * Unchanged active records are not requeued; failed and stale ones are.
   *
   * @covers ::enqueue
   * @dataProvider providerAlreadyQueued
   */
  public function testEnqueueAlreadyQueued(string $status, bool $requeued): void {
    $submission = $this->enqueued();
    $sid = (int) $submission->id();
    $this->setRecord($sid, ['status' => $status, 'attempts' => 2]);
    $generation = $this->field($sid, 'generation');
    $this->assertSame($requeued, $this->manager->enqueue($submission));
    $this->assertLocksFree();
    $this->assertSame($requeued ? 2 : 1, $this->queueCount());
    $this->assertSame($requeued ? 'pending' : $status, $this->field($sid, 'status'));
    $this->assertSame($requeued, $generation !== $this->field($sid, 'generation'));
    $this->assertEquals($requeued ? 0 : 2, $this->field($sid, 'attempts'));
  }

  /**
   * Data provider for testEnqueueAlreadyQueued().
   */
  public static function providerAlreadyQueued(): array {
    return [
      'pending' => ['pending', FALSE],
      'processing' => ['processing', FALSE],
      'completed' => ['completed', FALSE],
      'failed' => ['failed', TRUE],
      'stale' => ['stale', TRUE],
      'unknown' => ['other', TRUE],
    ];
  }

  /**
   * Force and changed input requeue; results and overrides are retained.
   *
   * @covers ::enqueue
   */
  public function testEnqueueForceAndChangedInput(): void {
    $submission = $this->enqueued();
    $sid = (int) $submission->id();
    $this->setRecord($sid, [
      'status' => 'completed',
      'results' => '{"kept":1}',
      'overrides' => '{"intent":"b"}',
      'labels' => ['intent:a'],
      'review_status' => 'reviewed',
      'error' => 'old',
      'attempts' => 3,
    ]);
    $generation = $this->field($sid, 'generation');
    $fingerprint = $this->field($sid, 'fingerprint');

    $this->assertTrue($this->manager->enqueue($submission, TRUE));
    $record = $this->manager->load($sid);
    $this->assertNotSame($generation, $record->get('generation')->value);
    $this->assertSame($fingerprint, $record->get('fingerprint')->value);
    $this->assertSame('pending', $record->get('status')->value);
    $this->assertSame('unreviewed', $record->get('review_status')->value);
    $this->assertEquals(0, $record->get('attempts')->value);
    $this->assertNull($record->get('error')->value);
    $this->assertSame([], $record->get('labels')->getValue());
    $this->assertSame('{"kept":1}', $record->get('results')->value);
    $this->assertSame('{"intent":"b"}', $record->get('overrides')->value);
    $this->assertSame(2, $this->queueCount());

    // Force requeues even an already pending record.
    $this->assertTrue($this->manager->enqueue($submission, TRUE));
    $this->assertSame(3, $this->queueCount());

    // Changed input requeues without force.
    $this->setRecord($sid, ['status' => 'completed']);
    $submission->setData(['message' => 'Changed'])->save();
    $this->assertTrue($this->manager->enqueue($submission));
    $this->assertNotSame($fingerprint, $this->field($sid, 'fingerprint'));
    $this->assertSame(4, $this->queueCount());
    $this->assertLocksFree();
  }

  /**
   * A queue that refuses the item marks the record failed.
   *
   * @covers ::enqueue
   */
  public function testEnqueueQueueFailure(): void {
    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())->method('createItem')->willReturn(FALSE);
    $queues = $this->createMock(QueueFactory::class);
    $queues->method('get')->with(AnalysisManager::QUEUE)->willReturn($queue);
    $manager = $this->createManager($queues);
    $submission = $this->submission();
    $sid = (int) $submission->id();
    $this->assertFalse($manager->enqueue($submission));
    $this->assertSame('failed', $this->field($sid, 'status'));
    $this->assertSame('Could not create a queue item.', $this->field($sid, 'error'));
    $this->assertLocksFree();
  }

  /**
   * A successful run stores the result under work and save locks.
   *
   * @covers ::process
   * @covers ::prepare
   */
  public function testProcessSuccess(): void {
    $submission = $this->enqueued();
    $sid = (int) $submission->id();
    $result = self::result();
    $this->classifier->expects($this->once())->method('classify')
      ->with(['message' => ['label' => 'Message', 'value' => 'Please refund']], self::questions(), 'jev-latest')
      ->willReturnCallback(function () use ($sid, $result) {
        // prepare() released the save lock and counted the attempt.
        $this->assertSame([self::WORK . $sid => TRUE], $this->lock->held);
        $this->assertSame('processing', $this->field($sid, 'status'));
        $this->assertEquals(1, $this->field($sid, 'attempts'));
        return $result;
      });
    $this->setRecord($sid, [
      'overrides' => '{"x":1}',
      'reviewer' => 5,
      'reviewed' => 6,
      'error' => 'old',
    ]);

    $this->assertNull($this->processDelay($this->item($sid)));
    $this->assertSame([
      ['acquire', self::WORK . $sid, 300, TRUE],
      ['acquire', self::SAVE . $sid, 30, TRUE],
      ['release', self::SAVE . $sid],
      ['acquire', self::SAVE . $sid, 30, TRUE],
      ['release', self::SAVE . $sid],
      ['release', self::WORK . $sid],
    ], $this->lock->log);

    $record = $this->manager->load($sid);
    $this->assertSame('completed', $record->get('status')->value);
    $this->assertSame('unreviewed', $record->get('review_status')->value);
    $this->assertSame('jev-test', $record->get('model')->value);
    $this->assertSame(json_encode($result), $record->get('results')->value);
    $this->assertSame(['intent:a', 'urgency:1.2', 'callback:yes'], array_column($record->get('labels')->getValue(), 'value'));
    $this->assertSame('{}', $record->get('overrides')->value);
    $this->assertEquals(0, $record->get('reviewer')->value);
    $this->assertEquals(0, $record->get('reviewed')->value);
    $this->assertNull($record->get('error')->value);
    $this->assertEquals(self::NOW, $record->get('changed')->value);
    $this->assertEquals(1, $record->get('attempts')->value);
  }

  /**
   * Low confidence or uncertain Noul marks the result for review.
   *
   * @covers ::process
   * @dataProvider providerNeedsReview
   */
  public function testProcessReviewStatus(float $confidence, float $noul, string $expected, string $callback): void {
    $sid = (int) $this->enqueued()->id();
    $this->classifier->method('classify')->willReturn(self::result($confidence, $noul));
    $this->manager->process($this->item($sid));
    $this->assertSame($expected, $this->field($sid, 'review_status'));
    $labels = array_column($this->manager->load($sid)->get('labels')->getValue(), 'value');
    $this->assertSame('callback:' . $callback, $labels[2]);
  }

  /**
   * Data provider for testProcessReviewStatus().
   */
  public static function providerNeedsReview(): array {
    return [
      'confident' => [0.9, 0.9, 'unreviewed', 'yes'],
      'low confidence' => [0.74, 0.9, 'needs_review', 'yes'],
      'confidence at threshold' => [0.75, 0.1, 'unreviewed', 'no'],
      'noul at review min' => [0.9, 0.35, 'needs_review', 'no'],
      'noul at threshold' => [0.9, 0.5, 'needs_review', 'yes'],
      'noul at review max' => [0.9, 0.65, 'needs_review', 'yes'],
      'noul above interval' => [0.9, 0.66, 'unreviewed', 'yes'],
      'noul below interval' => [0.9, 0.34, 'unreviewed', 'no'],
    ];
  }

  /**
   * A busy work lock delays the item without touching anything.
   *
   * @covers ::process
   */
  public function testProcessWorkLockBusy(): void {
    $sid = (int) $this->enqueued()->id();
    $this->lock->busy[self::WORK . $sid] = TRUE;
    $this->classifier->expects($this->never())->method('classify');
    $this->assertSame(60, $this->processDelay($this->item($sid)));
    $this->assertSame([['acquire', self::WORK . $sid, 300, FALSE]], $this->lock->log);
    $this->assertSame('pending', $this->field($sid, 'status'));
    $this->assertEquals(0, $this->field($sid, 'attempts'));
  }

  /**
   * A busy save lock in prepare() delays the item; work lock released.
   *
   * @covers ::process
   * @covers ::prepare
   */
  public function testProcessPrepareSaveLockBusy(): void {
    $sid = (int) $this->enqueued()->id();
    $this->lock->busy[self::SAVE . $sid] = TRUE;
    $this->classifier->expects($this->never())->method('classify');
    $this->assertSame(60, $this->processDelay($this->item($sid)));
    $this->assertSame([
      ['acquire', self::WORK . $sid, 300, TRUE],
      ['acquire', self::SAVE . $sid, 30, FALSE],
      ['release', self::WORK . $sid],
    ], $this->lock->log);
    $this->assertLocksFree();
    $this->assertSame('pending', $this->field($sid, 'status'));
    $this->assertEquals(0, $this->field($sid, 'attempts'));
  }

  /**
   * Missing record, other generation or inactive status: silent no-op.
   *
   * @covers ::process
   * @covers ::prepare
   * @dataProvider providerProcessSkips
   */
  public function testProcessSkips(string $case): void {
    $sid = (int) $this->enqueued()->id();
    $item = $this->item($sid);
    switch ($case) {
      case 'missing record':
        $item = ['sid' => $sid + 100, 'generation' => $item['generation']];
        break;

      case 'other generation':
        $item['generation'] = str_repeat('0', 32);
        break;

      case 'no generation':
        unset($item['generation']);
        break;

      default:
        $this->setRecord($sid, ['status' => $case]);
    }
    $this->classifier->expects($this->never())->method('classify');
    $this->assertNull($this->processDelay($item));
    $key = (int) $item['sid'];
    $this->assertSame([
      ['acquire', self::WORK . $key, 300, TRUE],
      ['acquire', self::SAVE . $key, 30, TRUE],
      ['release', self::SAVE . $key],
      ['release', self::WORK . $key],
    ], $this->lock->log);
    $this->assertEquals(0, $this->field($sid, 'attempts'));
    $this->assertContains($this->field($sid, 'status'), ['pending', $case]);
  }

  /**
   * Data provider for testProcessSkips().
   */
  public static function providerProcessSkips(): array {
    return [
      'missing record' => ['missing record'],
      'other generation' => ['other generation'],
      'no generation' => ['no generation'],
      'completed' => ['completed'],
      'failed' => ['failed'],
      'stale' => ['stale'],
    ];
  }

  /**
   * A 'processing' record (e.g. interrupted run) is processed again.
   *
   * @covers ::prepare
   */
  public function testProcessResumesProcessingRecord(): void {
    $sid = (int) $this->enqueued()->id();
    $this->setRecord($sid, ['status' => 'processing', 'attempts' => 1]);
    $this->classifier->expects($this->once())->method('classify')->willReturn(self::result());
    $this->manager->process($this->item($sid));
    $this->assertSame('completed', $this->field($sid, 'status'));
    $this->assertEquals(2, $this->field($sid, 'attempts'));
  }

  /**
   * A deleted submission deletes its record in prepare().
   *
   * @covers ::prepare
   */
  public function testProcessDeletedSubmission(): void {
    $sid = (int) $this->enqueued()->id();
    $item = $this->item($sid);
    $this->rawDeleteSubmission($sid);
    $this->classifier->expects($this->never())->method('classify');
    $this->assertNull($this->processDelay($item));
    $this->assertNull($this->manager->load($sid));
    $this->assertLocksFree();
  }

  /**
   * Reaching max_attempts fails the record without calling the provider.
   *
   * @covers ::prepare
   */
  public function testProcessMaxAttempts(): void {
    $sid = (int) $this->enqueued()->id();
    $this->setRecord($sid, ['attempts' => 3]);
    $this->classifier->expects($this->never())->method('classify');
    $this->assertNull($this->processDelay($this->item($sid)));
    $this->assertSame('failed', $this->field($sid, 'status'));
    $this->assertSame('Maximum processing attempts reached.', $this->field($sid, 'error'));
    $this->assertEquals(3, $this->field($sid, 'attempts'));
    $this->assertLocksFree();
  }

  /**
   * A changed fingerprint before the call marks the record stale.
   *
   * @covers ::prepare
   * @dataProvider providerStaleBeforeCall
   */
  public function testProcessStaleBeforeCall(string $case): void {
    $submission = $this->enqueued();
    $sid = (int) $submission->id();
    $item = $this->item($sid);
    match ($case) {
      'data' => $submission->setData(['message' => 'Changed'])->save(),
      'model' => $this->config('vactory_webform_typesafe.settings')->set('model', 'other')->save(),
      'handler disabled' => $this->setHandlerStatus(FALSE),
      'empty input' => $submission->setData(['message' => ''])->save(),
    };
    $this->classifier->expects($this->never())->method('classify');
    $this->assertNull($this->processDelay($item));
    $this->assertSame('stale', $this->field($sid, 'status'));
    $this->assertSame('Submission or configuration changed. Reanalyze to use current settings.', $this->field($sid, 'error'));
    $this->assertEquals(1, $this->field($sid, 'attempts'));
    $this->assertLocksFree();
  }

  /**
   * Data provider for testProcessStaleBeforeCall().
   */
  public static function providerStaleBeforeCall(): array {
    return [
      'data' => ['data'],
      'model' => ['model'],
      'handler disabled' => ['handler disabled'],
      'empty input' => ['empty input'],
    ];
  }

  /**
   * A snapshot exception in prepare() is recorded as a generic failure.
   *
   * @covers ::prepare
   * @covers ::process
   */
  public function testProcessPrepareSnapshotThrows(): void {
    $sid = (int) $this->enqueued()->id();
    $this->config('vactory_webform_typesafe.settings')->set('max_input_bytes', 5)->save();
    $this->classifier->expects($this->never())->method('classify');
    $this->assertSame(60, $this->processDelay($this->item($sid)));
    $this->assertSame('pending', $this->field($sid, 'status'));
    $this->assertSame('Classification failed. Check the connection, selected fields and input limits.', $this->field($sid, 'error'));
    $this->assertEquals(1, $this->field($sid, 'attempts'));
    $this->assertSame([
      ['acquire', self::WORK . $sid, 300, TRUE],
      ['acquire', self::SAVE . $sid, 30, TRUE],
      ['release', self::SAVE . $sid],
      ['acquire', self::SAVE . $sid, 30, TRUE],
      ['release', self::SAVE . $sid],
      ['release', self::WORK . $sid],
    ], $this->lock->log);
  }

  /**
   * Provider failures: retry policy, backoff, stored message.
   *
   * @covers ::process
   * @dataProvider providerFailures
   */
  public function testProcessFailure(int $maxAttempts, int $attempts, \Throwable $failure, string $status, ?int $delay, string $error): void {
    $this->config('vactory_webform_typesafe.settings')->set('max_attempts', $maxAttempts)->save();
    $sid = (int) $this->enqueued()->id();
    $this->setRecord($sid, ['attempts' => $attempts, 'changed' => 1]);
    $this->classifier->expects($this->once())->method('classify')->willThrowException($failure);
    $this->assertSame($delay, $this->processDelay($this->item($sid)));
    $this->assertSame($status, $this->field($sid, 'status'));
    $this->assertSame($error, $this->field($sid, 'error'));
    $this->assertEquals($attempts + 1, $this->field($sid, 'attempts'));
    $this->assertEquals(self::NOW, $this->field($sid, 'changed'));
    $this->assertLocksFree();
  }

  /**
   * Data provider for testProcessFailure().
   */
  public static function providerFailures(): array {
    $generic = 'Classification failed. Check the connection, selected fields and input limits.';
    $private = new \RuntimeException('PRIVATE');
    $type = new \TypeError('PRIVATE');
    $busy = static fn(int $after = 0) => new JevApiException('Busy', TRUE, $after);
    $bad = new JevApiException('Bad key');
    return [
      'generic first attempt' => [3, 0, $private, 'pending', 60, $generic],
      'generic second attempt' => [3, 1, $private, 'pending', 120, $generic],
      'generic last attempt' => [3, 2, $private, 'failed', NULL, $generic],
      'type error' => [3, 0, $type, 'pending', 60, $generic],
      'retryable api' => [3, 0, $busy(), 'pending', 60, 'Busy'],
      'retry after wins' => [3, 0, $busy(500), 'pending', 500, 'Busy'],
      'backoff wins' => [3, 1, $busy(30), 'pending', 120, 'Busy'],
      'backoff capped' => [20, 8, $busy(), 'pending', 3600, 'Busy'],
      'retry after not capped' => [20, 8, $busy(7200), 'pending', 7200, 'Busy'],
      'non retryable api' => [3, 0, $bad, 'failed', NULL, 'Bad key'],
      'retryable api last attempt' => [3, 2, $busy(500), 'failed', NULL, 'Busy'],
    ];
  }

  /**
   * A DelayedRequeueException from the classifier is rethrown as is.
   *
   * @covers ::process
   */
  public function testProcessClassifierDelayedRequeueRethrown(): void {
    $sid = (int) $this->enqueued()->id();
    $delayed = new DelayedRequeueException(5);
    $this->classifier->method('classify')->willThrowException($delayed);
    try {
      $this->manager->process($this->item($sid));
      $this->fail('Expected DelayedRequeueException.');
    }
    catch (DelayedRequeueException $e) {
      $this->assertSame($delayed, $e);
    }
    // Not recorded: the record stays 'processing' with the attempt counted.
    $this->assertSame('processing', $this->field($sid, 'status'));
    $this->assertNull($this->field($sid, 'error'));
    $this->assertEquals(1, $this->field($sid, 'attempts'));
    $this->assertLocksFree();
  }

  /**
   * The failure path delays when the save lock is busy; nothing is recorded.
   *
   * @covers ::process
   */
  public function testRecordFailureSaveLockBusy(): void {
    $sid = (int) $this->enqueued()->id();
    $this->classifier->method('classify')->willReturnCallback(function () use ($sid) {
      $this->lock->busy[self::SAVE . $sid] = TRUE;
      throw new JevApiException('Busy', TRUE, 900);
    });
    $this->assertSame(60, $this->processDelay($this->item($sid)));
    $this->assertSame('processing', $this->field($sid, 'status'));
    $this->assertNull($this->field($sid, 'error'));
    $this->assertLocksFree();
  }

  /**
   * A failure for a superseded generation is dropped.
   *
   * @covers ::process
   */
  public function testRecordFailureGenerationChanged(): void {
    $submission = $this->enqueued();
    $sid = (int) $submission->id();
    $this->classifier->method('classify')->willReturnCallback(function () use ($submission) {
      $this->assertTrue($this->manager->enqueue($submission, TRUE));
      throw new \RuntimeException('PRIVATE');
    });
    $item = $this->item($sid);
    $this->assertNull($this->processDelay($item));
    $this->assertNotSame($item['generation'], $this->field($sid, 'generation'));
    $this->assertSame('pending', $this->field($sid, 'status'));
    $this->assertNull($this->field($sid, 'error'));
    $this->assertEquals(0, $this->field($sid, 'attempts'));
    $this->assertLocksFree();
  }

  /**
   * A busy save lock after the call delays; the result is discarded.
   *
   * @covers ::process
   */
  public function testSaveLockBusyAfterCall(): void {
    $sid = (int) $this->enqueued()->id();
    $this->classifier->method('classify')->willReturnCallback(function () use ($sid) {
      $this->lock->busy[self::SAVE . $sid] = TRUE;
      return self::result();
    });
    $this->assertSame(60, $this->processDelay($this->item($sid)));
    $this->assertSame('processing', $this->field($sid, 'status'));
    $this->assertNull($this->field($sid, 'results'));
    $this->assertSame([
      ['acquire', self::WORK . $sid, 300, TRUE],
      ['acquire', self::SAVE . $sid, 30, TRUE],
      ['release', self::SAVE . $sid],
      ['acquire', self::SAVE . $sid, 30, FALSE],
      ['release', self::WORK . $sid],
    ], $this->lock->log);
    $this->assertLocksFree();
  }

  /**
   * A newer generation queued during the call survives the response.
   *
   * @covers ::process
   */
  public function testGenerationChangedDuringCall(): void {
    $submission = $this->enqueued();
    $sid = (int) $submission->id();
    $this->classifier->method('classify')->willReturnCallback(function () use ($submission) {
      $this->manager->enqueue($submission, TRUE);
      return self::result();
    });
    $this->assertNull($this->processDelay($this->item($sid)));
    $this->assertSame('pending', $this->field($sid, 'status'));
    $this->assertNull($this->field($sid, 'results'));
    $this->assertEquals(0, $this->field($sid, 'attempts'));
    $this->assertLocksFree();
  }

  /**
   * A submission deleted during the call deletes the record.
   *
   * @covers ::process
   */
  public function testSubmissionDeletedDuringCall(): void {
    $sid = (int) $this->enqueued()->id();
    $this->classifier->method('classify')->willReturnCallback(function () use ($sid) {
      $this->rawDeleteSubmission($sid);
      return self::result();
    });
    $this->assertNull($this->processDelay($this->item($sid)));
    $this->assertNull($this->manager->load($sid));
    $this->assertLocksFree();
  }

  /**
   * Input or configuration changes during the call mark the record stale.
   *
   * @covers ::process
   * @dataProvider providerStaleDuringCall
   */
  public function testStaleDuringCall(string $case): void {
    $submission = $this->enqueued();
    $sid = (int) $submission->id();
    $this->classifier->method('classify')->willReturnCallback(function () use ($case, $sid) {
      match ($case) {
        'data' => WebformSubmission::load($sid)->setData(['message' => 'Changed'])->save(),
        'snapshot throws' => $this->config('vactory_webform_typesafe.settings')->set('max_input_bytes', 5)->save(),
        'handler disabled' => $this->setHandlerStatus(FALSE),
      };
      return self::result();
    });
    $this->assertNull($this->processDelay($this->item($sid)));
    $record = $this->manager->load($sid);
    $this->assertSame('stale', $record->get('status')->value);
    $this->assertSame('Input changed during analysis. Reanalyze this submission.', $record->get('error')->value);
    $this->assertNull($record->get('results')->value);
    $this->assertEquals(1, $record->get('attempts')->value);
    $this->assertLocksFree();
  }

  /**
   * Data provider for testStaleDuringCall().
   */
  public static function providerStaleDuringCall(): array {
    return [
      'data' => ['data'],
      'snapshot throws' => ['snapshot throws'],
      'handler disabled' => ['handler disabled'],
    ];
  }

  /**
   * The settings() helper reads the enabled handler only.
   *
   * @covers ::settings
   */
  public function testSettings(): void {
    $webform = Webform::load('typesafe_test');
    $this->assertInstanceOf(WebformInterface::class, $webform);
    $this->assertSame(['message'], $this->manager->settings($webform)['fields']);
    $this->setHandlerStatus(FALSE);
    $this->assertNull($this->manager->settings(Webform::load('typesafe_test')));
  }

}
