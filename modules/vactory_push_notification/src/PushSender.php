<?php

namespace Drupal\vactory_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vactory_push_notification\Entity\Subscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription as PushSubscription;

/**
 * Sends push notifications.
 *
 * @package Drupal\vactory_push_notification
 */
class PushSender implements PushSenderInterface {

  /**
   * @var \Drupal\vactory_push_notification\KeysHelper
   */
  protected $keyHelper;

  /**
   * @var \Minishlink\WebPush\WebPush
   */
  protected $webPush;

  /**
   * @var \Drupal\vactory_push_notification\SubscriptionPurge
   */
  protected $purge;

  /**
   * The vactory_push_notification config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The TTL converter.
   *
   * @var \Drupal\vactory_push_notification\TTL
   */
  protected $ttl;

  /**
   * PushSender constructor.
   *
   * @param \Drupal\vactory_push_notification\KeysHelper $keysHelper
   *   The keys helper service.
   * @param \Drupal\vactory_push_notification\SubscriptionPurge $purge
   *   The subscription purge service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\vactory_push_notification\TTL $ttl
   *   The TTL converter.
   */
  public function __construct(
    KeysHelper $keysHelper,
    SubscriptionPurge $purge,
    ConfigFactoryInterface $config_factory,
    TTL $ttl
  ) {
    $this->keyHelper = $keysHelper;
    $this->purge = $purge;
    $this->config = $config_factory->get('vactory_push_notification.settings');
    $this->ttl = $ttl;
  }

  /**
   * Returns the push sender engine.
   *
   * @return \Minishlink\WebPush\WebPush
   *   The sender engine.
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   * @throws \ErrorException
   * @throws \Drupal\vactory_push_notification\InvalidTTLException
   */
  public function getWebPush() {
    if (!$this->webPush) {
      $auth = $this->keyHelper->getVapidAuth();
      $options = $this->getPushOptions();
      $this->webPush = new WebPush($auth, $options);
    }
    return $this->webPush;
  }

  /**
   * Sends notifications.
   *
   * @param \Drupal\vactory_push_notification\NotificationItem $item
   *   The notification item.
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   * @throws \ErrorException
   * @throws \Drupal\vactory_push_notification\InvalidTTLException
   */
  public function send(NotificationItem $item) {
    if (empty($item->ids)) {
      return;
    }
    $webPush = $this->getWebPush();
    $subscriptions = $this->createSubscriptions($item);
    foreach ($subscriptions as $subscription) {
      $webPush->sendNotification($subscription['subscription'], $subscription['payload']);
    }

    /** @var \Minishlink\WebPush\MessageSentReport $report */
    foreach ($webPush->flush(count($subscriptions)) as $report) {
      $this->purge->delete($report);
    }
  }

  /**
   * Gets the default push options.
   *
   * @return array
   *   The list of options.
   *
   * @throws \Drupal\vactory_push_notification\InvalidTTLException
   *
   * @see https://github.com/web-push-libs/web-push-php#notifications-and-default-options
   */
  public function getPushOptions() {
    $ttl = $this->config->get('push_ttl') ?: 100;
    return [
      'TTL' => $this->ttl->toMinutes($ttl),
      'urgency' => 'normal',
    ];
  }

  /**
   * Expands a notification item to a subscription list.
   *
   * @param \Drupal\vactory_push_notification\NotificationItem $item
   *   The notification item.
   *
   * @return \Minishlink\WebPush\Subscription[]
   *   A list of push subscriptions.
   *
   * @throws \ErrorException
   */
  protected function createSubscriptions(NotificationItem $item) {
    $notifications = [];
    foreach ($item->ids as $subscription_id) {
      if (!($subscription = Subscription::load($subscription_id))) {
        continue;
      }
      $notifications[] = [
        'subscription' => PushSubscription::create([
          'endpoint' => $subscription->getEndpoint(),
          'publicKey' => $subscription->getPublicKey(),
          'authToken' => $subscription->getToken(),
        ]),
        'payload' => $item->payload(),
      ];
    }
    return $notifications;
  }

}
