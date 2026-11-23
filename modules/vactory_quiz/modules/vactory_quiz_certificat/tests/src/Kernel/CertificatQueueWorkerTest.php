<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Psr\Log\LoggerInterface;

/**
 * Tests the failure path of the certificate queue worker (Redmine #71440).
 *
 * The worker threw an exception class of vactory_reminder, a module it does
 * not depend on. Without that module, a failing item broke the whole cron
 * run. A failing item is now kept in the queue, logged and retried later,
 * and the other items are still processed.
 *
 * The vactory_reminder module is not installed in this test.
 *
 * @group vactory_quiz_certificat
 */
class CertificatQueueWorkerTest extends KernelTestBase implements LoggerInterface {

  use RfcLoggerTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'vactory_html2pdf',
    'vactory_quiz',
    'vactory_quiz_certificat',
  ];

  /**
   * The schema of the module settings does not describe every key.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Name of the queue.
   */
  protected const QUEUE = 'quiz_certificat_queue_processor';

  /**
   * Messages logged in the channel of the module.
   *
   * @var string[]
   */
  protected $logged = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->container->get('logger.factory')->addLogger($this);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    if (($context['channel'] ?? '') === 'vactory_quiz_certificat') {
      $this->logged[] = strtr((string) $message, array_filter($context, 'is_scalar'));
    }
  }

  /**
   * Returns the worker.
   */
  protected function worker() {
    return $this->container->get('plugin.manager.queue_worker')->createInstance(self::QUEUE);
  }

  /**
   * Processes the queue as the cron of core does.
   */
  protected function runQueueAsCron(): void {
    $cron = $this->container->get('cron');
    if (method_exists($cron, 'lazyLoadItself')) {
      // The service is a lazy proxy: get the cron class of core behind it.
      $load = new \ReflectionMethod($cron, 'lazyLoadItself');
      $load->setAccessible(TRUE);
      $cron = $load->invoke($cron);
    }
    $method = new \ReflectionMethod($cron, 'processQueue');
    $method->setAccessible(TRUE);
    $method->invoke($cron, $this->container->get('queue')->get(self::QUEUE), $this->worker());
  }

  /**
   * The worker does not use a class of a module it does not depend on.
   */
  public function testWorkerDoesNotUseAnUndeclaredModule(): void {
    $this->assertFalse($this->container->get('module_handler')->moduleExists('vactory_reminder'));
    $class = new \ReflectionClass($this->worker());
    $this->assertStringNotContainsString('vactory_reminder', file_get_contents($class->getFileName()));
  }

  /**
   * Failing items stay in the queue and are retried by a later cron run.
   */
  public function testFailingItemsAreKeptAndRetriedLater(): void {
    $queue = $this->container->get('queue')->get(self::QUEUE);
    // Two items that cannot be processed: data missing, unknown user.
    $queue->createItem(['quiz_id' => 1]);
    $queue->createItem($this->itemData(999, 1));

    // Without vactory_reminder this used to end the run with a PHP error.
    $this->runQueueAsCron();

    // Both items were tried in the same run, and both are kept.
    $this->assertCount(2, $this->logged);
    $this->assertStringContainsString('item kept in the queue and retried later', $this->logged[0]);
    $this->assertStringContainsString('Insufficient item data', $this->logged[0]);
    $this->assertStringContainsString('No user has been found for user_id=999', $this->logged[1]);
    $this->assertSame(2, (int) $queue->numberOfItems());
    // They are not claimed again right away.
    $this->assertFalse($queue->claimItem());

    // Once the delay is over, a cron run tries them again: system_cron()
    // releases the items whose delay has expired.
    $this->container->get('database')->update('queue')
      ->fields(['expire' => 1])
      ->condition('name', self::QUEUE)
      ->execute();
    $queue->garbageCollection();
    $this->runQueueAsCron();
    $this->assertCount(4, $this->logged);
    $this->assertSame(2, (int) $queue->numberOfItems());
  }

  /**
   * A PDF that cannot be written keeps the item, with the reason logged.
   *
   * The private file path is not set in this test: "private://" is unknown.
   */
  public function testGenerationFailureKeepsTheItem(): void {
    // The first account is the super user: not used.
    $this->createUser();
    $user = $this->createUser();
    $quiz = Node::create(['type' => 'vactory_quiz', 'title' => 'Quiz']);
    $quiz->save();

    try {
      $this->worker()->processItem($this->itemData($user->id(), $quiz->id()));
      $this->fail('A certificate that is not generated must keep its item.');
    }
    catch (DelayedRequeueException $e) {
      $this->assertGreaterThan(0, $e->getDelay());
      $this->assertStringContainsString('private', $e->getMessage());
    }
    $this->assertCount(1, $this->logged);
    $this->assertStringContainsString('item kept in the queue and retried later', $this->logged[0]);
    $this->assertStringContainsString('private', $this->logged[0]);
  }

  /**
   * Builds the data of a queue item.
   *
   * @param int|string $uid
   *   User ID.
   * @param int|string $nid
   *   Quiz node ID.
   *
   * @return array
   *   The item data.
   */
  protected function itemData($uid, $nid): array {
    return [
      'html_output' => '<h1>Certificat</h1>',
      'output_file' => 'private://quiz_certificat/2026-11/a.pdf',
      'quiz_id' => $nid,
      'user_id' => $uid,
      'user_mark' => 10,
      'user_answers' => '[]',
    ];
  }

}
