<?php

namespace Drupal\vactory_reminder\Drush\Commands;

use Drupal\vactory_reminder\Services\ReminderQueueRunner;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands of vactory_reminder.
 */
final class ReminderCommands extends DrushCommands {

  /**
   * Constructs a ReminderCommands object.
   */
  public function __construct(
    private readonly ReminderQueueRunner $runner,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('vactory_reminder.queue_runner'));
  }

  /**
   * Runs the reminder queue: sends the reminders whose date has come.
   *
   * @command run-reminder-queue
   * @option time-limit Seconds to run (default: module setting).
   * @option lease-time Seconds an item is claimed for (default: module
   *   setting).
   * @usage drush run-reminder-queue --time-limit=60
   *   Runs the queue for at most 60 seconds.
   */
  public function runReminderQueue(
    array $options = ['time-limit' => NULL, 'lease-time' => NULL],
  ): void {
    $output = $this->output();
    $counts = $this->runner->run(
      $options['time-limit'] ? (int) $options['time-limit'] : NULL,
      $options['lease-time'] ? (int) $options['lease-time'] : NULL,
      static function (string $status, object $item, string $message = '') use ($output): void {
        $line = ucfirst($status) . ': ' . json_encode($item->data);
        $output->writeln($message ? $line . ' (' . $message . ')' : $line);
      },
    );
    if ($counts['remaining'] === NULL) {
      $this->logger()->warning('The reminder queue is already running.');
      return;
    }
    $this->logger()->success(dt('Processed: @processed, skipped: @skipped, failed: @failed, remaining: @remaining.', [
      '@processed' => $counts['processed'],
      '@skipped' => $counts['skipped'],
      '@failed' => $counts['failed'],
      '@remaining' => $counts['remaining'],
    ]));
  }

}
