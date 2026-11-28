<?php

namespace Drupal\vactory_reminder\Services;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Utility\Error;
use Drupal\vactory_reminder\SuspendCurrentItemException;

/**
 * Runs the reminder queue, from the cron or from Drush.
 */
class ReminderQueueRunner {

  /**
   * Name of the reminder queue and of its worker.
   */
  const QUEUE = 'reminder_queue_processor';

  /**
   * Constructs a ReminderQueueRunner object.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected AccountSwitcherInterface $accountSwitcher,
    protected QueueFactory $queueFactory,
    protected QueueWorkerManagerInterface $queueWorkerManager,
    protected LockBackendInterface $lock,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected Connection $database,
  ) {}

  /**
   * Processes the items of the reminder queue whose date has come.
   *
   * @param int|null $time_limit
   *   Seconds to run, or NULL for the module setting.
   * @param int|null $lease_time
   *   Seconds an item is claimed for, or NULL for the module setting.
   * @param callable|null $report
   *   Called with a status (processed, requeued, suspended, skipped, failed),
   *   the queue item and an optional message, for each item.
   *
   * @return array
   *   Counts: processed, skipped, failed, remaining.
   */
  public function run(?int $time_limit = NULL, ?int $lease_time = NULL, ?callable $report = NULL): array {
    $config = $this->configFactory->get('vactory_reminder.settings');
    $time_limit = $time_limit ?: (int) $config->get('reminder_time_limit') ?: 120;
    $lease_time = $lease_time ?: (int) $config->get('reminder_lease_time') ?: 300;
    $report = $report ?? static function () {};
    $logger = $this->loggerFactory->get('vactory_reminder');
    $counts = ['processed' => 0, 'skipped' => 0, 'failed' => 0];

    if (!$this->lock->acquire('vactory_reminder', 900.0)) {
      $logger->error('Attempting to re-run reminder while it is already running.');
      return $counts + ['remaining' => NULL];
    }

    // Allow execution to continue even if the request gets cancelled.
    @ignore_user_abort(TRUE);
    Environment::setTimeLimit(240);
    // Consistent permissions on every run.
    $this->accountSwitcher->switchTo(new AnonymousUserSession());

    try {
      $queue = $this->queueFactory->get(self::QUEUE);
      $worker = $this->queueWorkerManager->createInstance(self::QUEUE);
      // Only collect garbage when no item is waiting without a lease.
      if (!$this->hasUnclaimedItems()) {
        $queue->garbageCollection();
      }

      $end = time() + $time_limit;
      while (time() < $end && ($item = $queue->claimItem($lease_time))) {
        try {
          $worker->processItem($item->data);
          $queue->deleteItem($item);
          $counts['processed']++;
          $report('processed', $item);
        }
        catch (RequeueException $e) {
          $queue->releaseItem($item);
          $report('requeued', $item);
        }
        catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
          Error::logException($logger, $e);
          $report('suspended', $item, $e->getMessage());
          break;
        }
        catch (SuspendCurrentItemException $e) {
          // The date of the item has not come yet: it stays claimed until
          // its lease expires.
          $counts['skipped']++;
          $report('skipped', $item, $e->getMessage());
        }
        catch (\Exception $e) {
          // Left in the queue, to be processed again later.
          Error::logException($logger, $e);
          $counts['failed']++;
          $report('failed', $item, $e->getMessage());
        }
      }
      $counts['remaining'] = $queue->numberOfItems();
    }
    finally {
      $this->accountSwitcher->switchBack();
      $this->lock->release('vactory_reminder');
    }
    return $counts;
  }

  /**
   * Tells whether the queue has items that were never claimed.
   */
  protected function hasUnclaimedItems(): bool {
    try {
      return (bool) $this->database->query('SELECT COUNT(item_id) FROM {queue} WHERE name = :name AND expire = 0', [
        ':name' => self::QUEUE,
      ])->fetchField();
    }
    catch (\Exception $e) {
      // No queue table: no item.
      return FALSE;
    }
  }

  /**
   * Runs the queue, for code written for the former Drush service.
   *
   * Kept until 3.4 for code that calls the deprecated
   * vactory_reminder.commands service; new code calls run().
   *
   * @param array $options
   *   Options of the former Drush command: time-limit, lease-time.
   */
  public function runReminderQueue(array $options = []): void {
    $this->run(
      !empty($options['time-limit']) ? (int) $options['time-limit'] : NULL,
      !empty($options['lease-time']) ? (int) $options['lease-time'] : NULL,
    );
  }

}
