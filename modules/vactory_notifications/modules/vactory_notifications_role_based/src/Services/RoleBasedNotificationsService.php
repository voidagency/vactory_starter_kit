<?php

namespace Drupal\vactory_notifications_role_based\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Role based notifications service.
 */
class RoleBasedNotificationsService {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Notification config.
   *
   * @var array
   */
  protected $notificationConfig;

  /**
   * Role based notifications construct.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory
  ) {
    $this->configFactory = $configFactory;
    $this->notificationConfig = $this->configFactory->get('vactory_notifications_role_based.settings');
  }

  /**
   * Can current user send notifications.
   */
  public function canCurrentUserSendNotifications($bundle = ""): bool {
    $config_data = $this->notificationConfig->getRawData();
    if (empty($config_data)) {
      return TRUE;
    }
    $current_user_roles = \Drupal::currentUser()->getRoles();
    foreach ($current_user_roles as $role) {
      if (isset($config_data["{$role}_content_types"]) && empty($config_data["{$role}_content_types"])) {
        return TRUE;
      }
      if (in_array($bundle, $config_data["{$role}_content_types"] ?? [])) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
