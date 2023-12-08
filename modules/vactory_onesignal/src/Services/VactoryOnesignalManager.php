<?php

namespace Drupal\vactory_onesignal\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\onesignal\OneSignalService;

/**
 * Vactory onesignal manager.
 */
class VactoryOnesignalManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * One signal service.
   *
   * @var \Drupal\onesignal\OneSignalService
   */
  protected $oneSignalService;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * Frontend url from settings.
   *
   * @var string
   */
  protected $frontendUrl;

  /**
   * Constructs a new VactoryOnesignalManager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $languageManager,
    OneSignalService $oneSignalService,
    ConfigFactoryInterface $configFactory,
    Connection $databaseConnection
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $languageManager;
    $this->oneSignalService = $oneSignalService;
    $this->configFactory = $configFactory;
    $this->databaseConnection = $databaseConnection;
    $this->frontendUrl = trim(Settings::get('BASE_FRONTEND_URL', 'frontend_url'), '/');
  }

  /**
   * Generate onesignal push notification.
   *
   * @param array $headings
   *   Notification heading array with translations:
   *   Example: ['en' => 'Welcome', 'fr' => 'Bienvenue', 'ar' => 'مرحبا'].
   * @param array $contents
   *   Notification content array with translations:
   *   Example: ['en' => 'Hello','fr' => 'Salut'].
   * @param string $redirect_path
   *   When clicking the notif redrect to this path.
   * @param array $drupal_users_ids
   *   Drupal concerned users ids, empty means push to all subscribed devices.
   * @param array $subscription_ids
   *   Concerned onesignal subscription ids.
   */
  public function onesignalNotifyUsers(array $headings, array $contents, $redirect_path = '/', array $drupal_users_ids = [], array $subscription_ids = []) {
    $onesignal_data = [
      'headings' => $headings,
      'contents' => $contents,
      'isChrome' => TRUE,
      'isAnyWeb' => TRUE,
      'isChromeWeb' => TRUE,
      'web_url' => $this->frontendUrl . $redirect_path,
    ];
    $onesignal_data['app_id'] = $this->configFactory->get('onesignal.config')->get('onesignal_app_id') ?? '';
    $devices_ids = [];
    if (!empty($drupal_users_ids)) {
      $query = $this->databaseConnection->select('user__field_user_device_ids', 'ufd')
        ->fields('ufd', ['field_user_device_ids_value'])
        ->condition('ufd.entity_id', $drupal_users_ids, 'IN');
      $result = $query->execute();
      $devices_ids = $result->fetchCol();
      $devices_ids = array_filter($devices_ids);
    }
    $subscription_ids = array_unique(array_merge($subscription_ids, $devices_ids));
    if (!empty($subscription_ids)) {
      // Push notifi to concerned users devices.
      $onesignal_data['include_subscription_ids'] = $devices_ids;
    }
    $response = $this->oneSignalService->addNotification($onesignal_data);
    if (!isset($response['id']) && isset($response['errors'])) {
      $errors = '<br>•';
      $errors .= implode('<br>•', $response['errors']);
      \Drupal::logger('vactory_onesignal')->error('Onesignal API call errors: ' . $errors);
    }
  }

}
