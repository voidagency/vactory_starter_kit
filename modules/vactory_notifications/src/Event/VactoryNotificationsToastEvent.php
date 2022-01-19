<?php

namespace Drupal\vactory_notifications\Event;

use Drupal\vactory_notifications\Entity\NotificationsInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Vactory notification toast event.
 */
class VactoryNotificationsToastEvent extends Event {

  /**
   * Event name.
   */
  const EVENT_NAME = 'vactory_notification_toast';

  /**
   * Notification entity.
   *
   * @var NotificationsInterface
   */
  protected $notificationEntity;

  public function __construct(NotificationsInterface $notificationEntity) {
    $this->notificationEntity = $notificationEntity;
  }

  /**
   * Notification entity getter.
   */
  public function getNotificationEntity(): NotificationsInterface {
    return $this->notificationEntity;
  }

  /**
   * Notification entity setter.
   */
  public function setNotificationEntity(NotificationsInterface $notificationEntity): void {
    $this->notificationEntity = $notificationEntity;
  }

}
