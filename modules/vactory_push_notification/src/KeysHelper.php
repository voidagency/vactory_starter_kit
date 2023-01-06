<?php

namespace Drupal\vactory_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Manages APN and FCM keys.
 */
class KeysHelper {

  const SETTINGS = 'vactory_push_notification.settings';

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var string
   */
  private $apnKey;

  /**
   * @var string
   */
  private $fcmKey;

  /**
   * HelperService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->getEditable(self::SETTINGS);
  }

  /**
   * Returns a APN key.
   *
   * @return string
   *   The APN key.
   */
  public function getApnKey() {
    if (!$this->apnKey) {
      $this->apnKey = $this->config->get('apn_key');
    }
    return $this->apnKey;
  }

  /**
   * Returns a FCM key.
   *
   * @return string
   *   The FCM key.
   */
  public function getFcmKey() {
    if (!$this->fcmKey) {
      $this->fcmKey = $this->config->get('fcm_key');
    }
    return $this->fcmKey;
  }

  /**
   * Returns whether keys (APN and FCM) defined.
   *
   * @return bool
   */
  public function isKeysDefined() {
    $apn = $this->getApnKey();
    $fcm = $this->getFcmKey();
    return $apn && $fcm;
  }

  /**
   * Validate auth keys.
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   */
  protected function validateKeys() {
    if (!$this->isKeysDefined()) {
      throw new AuthKeysException('FCM and APN keys are required.');
    }
  }

  /**
   * Save APN and FCM keys to the module config settings.
   *
   * @return $this
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   */
  public function save() {
    $this->validateKeys();

    $this->config
      ->set('apn_key', $this->getApnKey())
      ->set('fcm_key', $this->getFcmKey())
      ->save();

    return $this;
  }

  /**
   * Set APN and FCM keys.
   *
   * @param string $apn_key
   *   The APN key.
   * @param string $apn_key
   *   The FCM key.
   *
   * @return $this
   */
  public function setKeys($apn_key, $fcm_key) {
    $this->apnKey = $apn_key;
    $this->fcmKey = $fcm_key;
    return $this;
  }

}
