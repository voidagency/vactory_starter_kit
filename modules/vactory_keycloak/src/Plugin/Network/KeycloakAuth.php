<?php

namespace Drupal\vactory_keycloak\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\vactory_keycloak\Settings\KeycloakAuthSettings;
use pviojo\OAuth2\Client\Provider\Keycloak;

/**
 * Defines a Network Plugin for Social Auth Keycloak.
 *
 * @package Drupal\vactory_keycloak\Plugin\Network
 *
 * @Network(
 *   id = "vactory_keycloak",
 *   social_network = "Keycloak",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\vactory_keycloak\Settings\KeycloakAuthSettings",
 *       "config_id": "vactory_keycloak.settings"
 *     }
 *   }
 * )
 */
class KeycloakAuth extends NetworkBase implements KeycloakAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \pviojo\OAuth2\Client\Provider\Keycloak|false
   *   The initialized 3rd party library instance.
   *   False if library could not be initialized.
   *
   * @throws \Drupal\social_api\SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\pviojo\OAuth2\Client\Provider\Keycloak';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP League OAuth2 library for Keycloak not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\vactory_keycloak\Settings\KeycloakAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'authServerUrl'         => $settings->getAppServerUrl(),
        'realm'                 => $settings->getAppRealm(),
        'clientId'              => $settings->getAppClientId(),
        'clientSecret'          => $settings->getAppClientSecret(),
        'redirectUri'           => Url::fromRoute('vactory_keycloak.callback')->setAbsolute()->toString(),
      ];

      return new Keycloak($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\vactory_keycloak\Settings\KeycloakAuthSettings $settings
   *   The Social Auth Keycloak settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(KeycloakAuthSettings $settings) {
    $app_server_url = $settings->getAppServerUrl();
    $app_realm = $settings->getAppRealm();
    $app_client_id = $settings->getAppClientId();

    if (!$app_server_url || !$app_realm || !$app_client_id) {
      $this->loggerFactory
        ->get('vactory_keycloak')
        ->error('Define App Client ID, App Server URL and App Realm on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
