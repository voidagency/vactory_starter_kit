<?php

namespace Drupal\vactory_reminder\Commands;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Database\Database;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\ProxyClass\Lock\DatabaseLockBackend;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\vactory_reminder\SuspendCurrentItemException;
use Drush\Commands\DrushCommands;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Vactory reminder run reminder queue drush command.
 */
class VactoryRunReminderQueueCommand extends DrushCommands {

  /**
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Account switch service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  private $accountSwitcher;

  /**
   * Queue Factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  private $queueFactory;

  /**
   * Queue worker manager service.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  private $queueWorkerManager;

  /**
   * Lock backend service.
   *
   * @var \Drupal\Core\ProxyClass\Lock\DatabaseLockBackend
   */
  private $lockBackend;

  /**
   * Logger Factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $loggerFactory;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    AccountSwitcherInterface $accountSwitcher,
    QueueFactory $queueFactory,
    QueueWorkerManagerInterface $queueWorkerManager,
    DatabaseLockBackend $lockBackend,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    parent::__construct();
    $this->configFactory = $configFactory;
    $this->accountSwitcher = $accountSwitcher;
    $this->queueFactory = $queueFactory;
    $this->queueWorkerManager = $queueWorkerManager;
    $this->lockBackend = $lockBackend;
    $this->loggerFactory = $loggerFactory->get('vactory_reminder');
  }

  /**
   * Run reminder queue.
   *
   * @command run-reminder-queue
   * @aliases rrq
   * @options arr An option that takes multiple values.
   * @options time-limit The maximum number of seconds allowed to run the queue.
   * @options lease-time How long the processing is expected to take in seconds.
   * @usage drush rrq
   *   Run reminder queue.
   * @usage drush rrq --time-limit
   *   Run reminder queue with a time limit
   * @usage drush rrq --lease-time
   *   Run reminder queue with a lease time
   */
  public function runReminderQueue($options = [
    'time-limit' => '',
    'lease-time' => '',
  ]) {
    $config = $this->configFactory->get('vactory_reminder.settings');
    $operation_limit = isset($options['time-limit']) && !empty($options['time-limit']) ? $options['time-limit'] : $config->get('reminder_time_limit');
    $lease_time = isset($options['lease-time']) && !empty($options['lease-time']) ? $options['lease-time'] : $config->get('reminder_lease_time');

    // Log information.
    $start = microtime(TRUE);
    $count = 0;
    $skipped = 0;
    $failed = 0;

    // Allow execution to continue even if the request gets cancelled.
    @ignore_user_abort(TRUE);
    // Try to allocate enough time to run all the hook_cron implementations.
    Environment::setTimeLimit(240);

    // Force the current user to anonymous to ensure consistent permissions on
    // runs.
    $this->accountSwitcher->switchTo(new AnonymousUserSession());

    if (!$this->lockBackend->acquire('vactory_reminder', 900.0)) {
      // Reminder is still running normally.
      $this->loggerFactory->error('Attempting to re-run reminder while it is already running.');
      return;
    }
    else {
      $this->lockBackend->release('vactory_reminder');
    }

    $queue_name = 'reminder_queue_processor';
    $queue = $this->queueFactory->get($queue_name);
    $queue_worker = $this->queueWorkerManager->createInstance('reminder_queue_processor');

    // Clean up any garbage in the queue service.
    // Only garbage collector if there is no expired item.
    if (!$this->isQueueStillHavingExpiredItems()) {
      $queue->garbageCollection();
    }

    $output = $this->output();

    $end = time() + $operation_limit;
    while (time() < $end && ($item = $queue->claimItem($lease_time))) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $count++;
        $output->writeln('<info>Proceeded: </info>' . json_encode($item));
      }
      catch (RequeueException $e) {
        // The worker requested the task be immediately requeued.
        $output->writeln('<question>Requeue: </question>' . json_encode($item));
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);

        watchdog_exception('vactory_reminder', $e);
        $output->writeln('<error>Suspend: </error>' . json_encode($item));
        $output->writeln('<error>Suspend (Reason): </error>' . $e->getMessage());

        // Skip to the next queue.
        break;
      }
      catch (SuspendCurrentItemException $e) {
        $output->writeln('<comment>Skipped: </comment>' . json_encode($item));
        $output->writeln('<comment>Skipped (Reason): </comment>' . $e->getMessage());
        // Skipped item, do nothing.
        $skipped++;
      }
      catch (\Exception $e) {
        $output->writeln('<error>Failed: </error>' . json_encode($item));
        $output->writeln('<error>Failed (Reason): </error>' . $e->getMessage());
        // In case of any other kind of exception, log it and leave the item.
        // In the queue to be processed again later.
        watchdog_exception('vactory_reminder', $e);
        $failed++;
      }
    }

    $elapsed = microtime(TRUE) - $start;
    $mem_usage = memory_get_usage();
    $mem_usage = $this->memoryConvert($mem_usage);

    // Restore the user.
    $this->accountSwitcher->switchBack();

    $output->writeln([
      '',
      '<info>==========================</>',
      '<info>Finished Processing</>',
      '<info>' . dt('Processed: @count', ['@count' => $count]) . '</>',
      '<info>' . dt('Skipped: @skipped', ['@skipped' => $skipped]) . '</>',
      '<info>' . dt('Failed: @failed', ['@failed' => $failed]) . '</>',
      '<info>' . dt('Remaining: @remaining', ['@remaining' => $queue->numberOfItems()]) . '</>',
      '<info>' . dt('Elapsed Time: @elapsed', ['@elapsed' => round($elapsed, 2)]) . '</>',
      '<info>' . dt('Used Memory: @mem_used', ['@mem_used' => $mem_usage]) . '</>',
      '<info>==========================</>',
      '',
    ]);
  }

  /**
   * Convert memory_get_usage.
   */
  private function memoryConvert($size) {
    $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
  }

  /**
   * {@inheritdoc}
   */
  private function isQueueStillHavingExpiredItems() {
    try {
      $total = (int) Database::getConnection()->query('SELECT COUNT(item_id) FROM {queue} WHERE name = :name AND expire = 0', [
        ':name' => 'reminder_queue_processor',
      ])
        ->fetchField();

      return (bool) $total > 0;
    }
    catch (\Exception $e) {
      // If there is no table there cannot be any items.
      return FALSE;
    }
  }

}
