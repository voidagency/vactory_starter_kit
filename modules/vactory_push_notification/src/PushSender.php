<?php

namespace Drupal\vactory_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vactory_push_notification\Entity\Subscription;
use Drupal\vactory_push_notification\Lib\Push;
use Drupal\vactory_push_notification\Lib\Subscription as PushSubscription;

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
   * @var \Drupal\vactory_push_notification\Lib\Push
   */
  protected $push;

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
   * Push Service Plugins
   * 
   * @var \Drupal\vactory_push_notification\PushServiceInterface[]
   */
  protected $plugins = [];

  /**
   * PushSender constructor.
   *
   * @param \Drupal\vactory_push_notification\KeysHelper $keysHelper
   *   The keys helper service.
   * @param \Drupal\vactory_push_notification\SubscriptionPurge $purge
   *   The subscription purge service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(
    KeysHelper $keysHelper,
    SubscriptionPurge $purge,
    ConfigFactoryInterface $config_factory,
    PushServicesPluginManager $push_services_plugin_manager
  ) {
    $this->keyHelper = $keysHelper;
    $this->purge = $purge;
    $this->config = $config_factory->get('vactory_push_notification.settings');

    $definitions = $push_services_plugin_manager->getDefinitions();
    $plugins = [];
    foreach ($definitions as $definition) {
      $plugin_id = $definition['id'];
      $plugin = $push_services_plugin_manager->createInstance($plugin_id);
      $plugins[$plugin_id] = $plugin;
    }

    $this->plugins = $plugins;
  }

  /**
   * Returns the push sender engine.
   *
   * @return \Drupal\vactory_push_notification\Lib\Push
   *   The sender engine.
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   * @throws \ErrorException
   */
  public function getPush() {
    if (!$this->push) {
      $options = [];
      $this->push = new Push($this->plugins, $options);
    }
    return $this->push;
  }

  /**
   * Sends notifications.
   *
   * @param \Drupal\vactory_push_notification\NotificationItem $item
   *   The notification item.
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   * @throws \ErrorException
   */
  public function send(NotificationItem $item) {
    if (empty($item->ids)) {
      return;
    }
    
    // todo: move to top
    $isDevelEnabled = \Drupal::service('module_handler')->moduleExists('devel');
    $webPush = $this->getPush();

    $subscriptions = $this->createSubscriptions($item);

    foreach ($subscriptions as $subscription) {
      $webPush->queueNotification($subscription['subscription'], $subscription['payload']);
    }

    /** @var \Drupal\vactory_push_notification\Lib\MessageSentReport $report */
    foreach ($webPush->flush(count($subscriptions)) as $report) {
      if ($isDevelEnabled) {
        dpm($report);
      }
      // $this->purge->delete($report);
    }

  }

  /**
   * Expands a notification item to a subscription list.
   *
   * @param \Drupal\vactory_push_notification\NotificationItem $item
   *   The notification item.
   *
   * @return \Drupal\vactory_push_notification\Lib\Subscription[]
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
          'token' => $subscription->getToken(),
        ]),
        'payload' => $item->payload(),
      ];
    }
    return $notifications;
  }

}
