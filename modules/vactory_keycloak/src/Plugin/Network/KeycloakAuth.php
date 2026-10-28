<?php

namespace Drupal\vactory_keycloak\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Settings\SettingsInterface;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use GuzzleHttp\Client as HttpClient;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

/**
 * Defines a Network Plugin for Social Auth Keycloak.
 *
 * @package Drupal\vactory_keycloak\Plugin\Network
 *
 * @Network(
 *   id = "vactory_keycloak",
 *   short_name = "keycloak",
 *   social_network = "Keycloak",
 *   img_path = "img/keycloak_logo.svg",
 *   type = "social_auth",
 *   class_name = "\Stevenmaguire\OAuth2\Client\Provider\Keycloak",
 *   auth_manager = "\Drupal\vactory_keycloak\KeycloakAuthManager",
 *   routes = {
 *     "redirect": "vactory_keycloak.redirect_to_kc",
 *     "callback": "vactory_keycloak.callback",
 *     "settings_form": "vactory_keycloak.settings_form",
 *   },
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
   * {@inheritdoc}
   */
  protected function initSdk(): mixed {
    if (!class_exists(Keycloak::class)) {
      throw new SocialApiException(sprintf('The PHP League OAuth2 library for Keycloak not found. Class: %s.', Keycloak::class));
    }

    /** @var \Drupal\vactory_keycloak\Settings\KeycloakAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'authServerUrl' => $settings->getAppServerUrl(),
        'realm' => $settings->getAppRealm(),
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => $this->getCallbackUrl()->setAbsolute()->toString(),
      ];

      if ($settings->noCertVerification()) {
        return new Keycloak($league_settings, [
          'httpClient' => new HttpClient([
            'timeout' => 0,
            'verify' => FALSE,
          ]),
        ]);
      }

      return new Keycloak($league_settings);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateConfig(SettingsInterface $settings): bool {
    /** @var \Drupal\vactory_keycloak\Settings\KeycloakAuthSettings $settings */
    if (!$settings->getAppServerUrl() || !$settings->getAppRealm() || !$settings->getClientId()) {
      $this->loggerFactory
        ->get('vactory_keycloak')
        ->error('Define App Client ID, App Server URL and App Realm on module settings.');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Keycloak routes have no {network} parameter.
   */
  public function getRedirectUrl(array $route_options = []): Url {
    return Url::fromRoute('vactory_keycloak.redirect_to_kc', [], $route_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getCallbackUrl(array $route_options = []): Url {
    return Url::fromRoute('vactory_keycloak.callback', [], $route_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsFormUrl(array $route_options = []): Url {
    return Url::fromRoute('vactory_keycloak.settings_form', [], $route_options);
  }

}
