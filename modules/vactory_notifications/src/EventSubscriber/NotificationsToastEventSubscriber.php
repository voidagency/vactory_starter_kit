<?php

namespace Drupal\vactory_notifications\EventSubscriber;

use Drupal\Component\Datetime\Time;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use Drupal\vactory_notifications\Event\VactoryNotificationsToastEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Points Edit event subscriber class.
 */
class NotificationsToastEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Points Edit event subscriber constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    Time $time
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      VactoryNotificationsToastEvent::EVENT_NAME => 'createNotificationToast',
    ];
  }

  /**
   * Edit user points event handler.
   */
  public function createNotificationToast(VactoryNotificationsToastEvent $event) {
    $notification = $event->getNotificationEntity();
    $concerned_users = Json::decode($notification->get('notification_concerned_users')->value);
    if (!empty($concerned_users)) {
      $online_users = $this->getOnlineUsers();
      if (!empty($online_users)) {
        $concerned_users = array_intersect($online_users, $concerned_users);
        $users = $this->entityTypeManager->getStorage('user')
          ->loadMultiple($concerned_users);
        foreach ($users as $user) {
          $user_toast = Json::decode($user->get('field_notification_toast')->value);
          if (!empty($user_toast) && is_array($user_toast)) {
            $user_toast = array_slice($user_toast, -3);
          }
          else {
            $user_toast = [];
          }
          $user_toast[] = $notification->id();
          $user->set('field_notification_toast', Json::encode($user_toast))
            ->save();
        }
      }
    }
  }

  /**
   * Get online users.
   */
  public function getOnlineUsers() {
    $results = $this->database->select('sessions', 's')
      ->fields('s', ['uid'])
      // We are interested in users which were connected last 30min.
      ->condition('timestamp', $this->time->getCurrentTime() - 1800, '>=')
      ->execute()
      ->fetchCol();
    return $results;
  }

}
