<?php

namespace Drupal\vactory_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Minishlink\WebPush\VAPID;

/**
 * Manages public and private keys.
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
  private $publicKey;

  /**
   * @var string
   */
  private $privateKey;

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
   * Returns a public key.
   *
   * @return string
   *   The public key.
   */
  public function getPublicKey() {
    if (!$this->publicKey) {
      $this->publicKey = $this->config->get('public_key');
    }
    return $this->publicKey;
  }

  /**
   * Returns a private key.
   *
   * @return string
   *   The private key.
   */
  public function getPrivateKey() {
    if (!$this->privateKey) {
      $this->privateKey = $this->config->get('private_key');
    }
    return $this->privateKey;
  }

  /**
   * Generates a public and private keys.
   *
   * @return array
   *   The list of two keys indexed by 'publicKey' and 'privateKey'.
   *
   * @throws \ErrorException
   */
  public function generateKeys() {
    $keys = VAPID::createVapidKeys();
    $this->publicKey = $keys['publicKey'];
    $this->privateKey = $keys['privateKey'];
    return $this;
  }

  /**
   * Returns whether keys (public and private) defined.
   *
   * @return bool
   */
  public function isKeysDefined() {
    $public = $this->getPublicKey();
    $private = $this->getPublicKey();
    return $public && $private;
  }

  /**
   * Returns an array suitable for VAPID::validate().
   *
   * @see VAPID::validate()
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   *   When public or/and private keys isn't defined.
   *
   * @return array
   */
  public function getVapidAuth() {
    $this->validateKeys();

    return [
      'VAPID' => [
        'subject' => Url::fromRoute('<front>', [], [
          'absolute' => TRUE
        ])->toString(),
        'publicKey' => $this->getPublicKey(),
        'privateKey' => $this->getPrivateKey(),
      ],
    ];
  }

  /**
   * Validate auth keys.
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   */
  protected function validateKeys() {
    if (!$this->isKeysDefined()) {
      throw new AuthKeysException('Public and private keys are required.');
    }
  }

  /**
   * Save public and private keys to the module config settings.
   *
   * @return $this
   *
   * @throws \Drupal\vactory_push_notification\AuthKeysException
   */
  public function save() {
    $this->validateKeys();

    $this->config
      ->set('public_key', $this->getPublicKey())
      ->set('private_key', $this->getPrivateKey())
      ->save();

    return $this;
  }

  /**
   * Set public and private keys.
   *
   * @param string $public_key
   *   The public key.
   * @param string $private_key
   *   The private key.
   *
   * @return $this
   */
  public function setKeys($public_key, $private_key) {
    $this->publicKey = $public_key;
    $this->privateKey = $private_key;
    return $this;
  }

}
