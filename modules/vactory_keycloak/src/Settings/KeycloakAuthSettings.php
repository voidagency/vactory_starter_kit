<?php

namespace Drupal\vactory_keycloak\Settings;

use Drupal\social_auth\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Keycloak app settings.
 */
class KeycloakAuthSettings extends SettingsBase implements KeycloakAuthSettingsInterface {

  /**
   * Application Server URL.
   *
   * @var string|null
   */
  protected $appServerUrl;

  /**
   * Application client realm.
   *
   * @var string|null
   */
  protected $appRealm;

  /**
   * {@inheritdoc}
   */
  public function getAppServerUrl(): ?string {
    if (!$this->appServerUrl) {
      $this->appServerUrl = $this->config->get('app_server_url');
    }
    return $this->appServerUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppRealm(): ?string {
    if (!$this->appRealm) {
      $this->appRealm = $this->config->get('app_realm');
    }
    return $this->appRealm;
  }

  /**
   * {@inheritdoc}
   */
  public function noCertVerification(): bool {
    return (bool) $this->config->get('no_verify');
  }

}
