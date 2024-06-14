<?php

namespace Drupal\vactory_notifications\Commands;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;

/**
 * A Drush command file.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml.
 */
class NotificationsDrushCommands extends DrushCommands {

  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Drush command service constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerChannelFactory
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
  }

  /**
   * Clear expired notifications data.
   *
   * @command clear-expired-notifications
   * @aliases cen
   *
   * @usage cen
   *   Clear expired notifications using batch mode.
   */
  #[CLI\Command(name: 'clear-expired-notifications',
    aliases: ['cen', 'clear-expired-notifications'])]
  public function clearNotificationsData($options = []) {
    $this->loggerChannelFactory->get('notifications_cleaner')
      ->info('Clearing expired notifications entities batch operations start');
    $this->output()->writeln('Clearing expired notifications entities batch operations start');

    $config = \Drupal::configFactory()->get('vactory_notifications.settings');
    $current_date = DrupalDateTime::createFromTimestamp(time(), new \DateTimeZone('UTC'));
    $diff_days = $config->get('notifications_lifetime') ?? 6;
    $current_date->modify("-{$diff_days} day");
    $relative_timestamp = $current_date->getTimestamp();
    $ids = $this->entityTypeManager->getStorage('notifications_entity')
      ->getQuery()
      ->condition('created', $relative_timestamp, '<=')
      ->accessCheck(FALSE)
      ->execute();
    if (!empty($ids)) {
      $ids_chunk = array_chunk($ids, 100);
      foreach ($ids_chunk as $ids) {
        $operations[] = [
          'vactory_notifications_batch_delete',
          [$ids],
        ];
      }
      if (!empty($operations)) {
        $batch = [
          'title'      => 'Process of cleaning expired notifications',
          'operations' => $operations,
          'finished'   => 'vactory_notification_clean_finished',
        ];
        batch_set($batch);
        drush_backend_batch_process();
        $this->output()->writeln('Clean expired notification entities batch operations end');
        $this->loggerChannelFactory->get('notifications_cleaner')
          ->info('Clean expired notification entities batch operations end');
      }
    }
    else {
      $this->output()->writeln('No expired notifications has been found');
      $this->loggerChannelFactory->get('notifications_cleaner')
        ->info('No expired notifications has been found');
    }
  }

}
