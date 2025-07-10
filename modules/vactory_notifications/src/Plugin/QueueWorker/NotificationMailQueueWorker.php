<?php

namespace Drupal\vactory_notifications\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "vactory_notification_mail_queue",
 *   title = @Translation("Mail Notification Queue Worker"),
 *   cron = {"time" = 60}
 * )
 */
class NotificationMailQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  protected $notificationManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $notification_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->notificationManager = $notification_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vactory_notifications.manager')
    );
  }

  public function processItem($data) {
    $this->notificationManager->sendNotificationByMail(
      $data['subject'],
      $data['email'],
      $data['message']
    );
  }
}