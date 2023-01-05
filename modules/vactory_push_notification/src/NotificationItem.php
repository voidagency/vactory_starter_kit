<?php

namespace Drupal\vactory_push_notification;

use Drupal\Component\Serialization\Json;

/**
 * Notification queue item.
 */
class NotificationItem {

  /**
   * @var int[]
   *   The list of Subscription entity ID.
   */
  public $ids = [];

  /**
   * @var string
   *   The notification title.
   */
  public $title = '';

  /**
   * @var string
   *   The notification message (body).
   */
  public $body = '';

  /**
   * @var string
   *   The notification url.
   */
  public $url = '';

  /**
   * @var string
   *  The notification image/icon.
   */
  public $icon = '';

  /**
   * @var string
   *   The bundle name. What bundle is used to create this item.
   */
  public $bundle = '';

  /**
   * NotificationItem constructor.
   *
   * @param string $title
   *   The notification title.
   *
   * @param string $body
   *   The notification message (body).
   */
  public function __construct($title = '', $body = '') {
    $this->title = $title;
    $this->body = $body;
  }

  /**
   * Converts the item to a push payload.
   *
   * @return string
   *   A JSON encoded payload.
   */
  public function payload() {
    return JSON::encode([
      'title' => $this->title,
      'body' => $this->body,
      'url' => $this->url,
      'icon' => $this->icon,
    ]);
  }
}
