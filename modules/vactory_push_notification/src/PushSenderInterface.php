<?php

namespace Drupal\vactory_push_notification;

/**
 * PushSenderInterface
 *
 * @package Drupal\vactory_push_notification
 */
interface PushSenderInterface {

  /**
   * Sends a notification item.
   *
   * @param \Drupal\vactory_push_notification\NotificationItem $item
   *   The notification item.
   */
  public function send(NotificationItem $item);

}
