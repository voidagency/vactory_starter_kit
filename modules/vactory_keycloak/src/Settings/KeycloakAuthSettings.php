<?php

namespace Drupal\vactory_keycloak\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Keycloak app settings.
 */
class KeycloakAuthSettings extends SettingsBase implements KeycloakAuthSettingsInterface {

  /**
   * Application Server URL.
   *
   * @var string
   */
  protected $appServerUrl;

  /**
   * Application client realm.
   *
   * @var string
   */
  protected $appRealm;

  /**
   * Application client id.
   *
   * @var string
   */
  protected $appClientId;

  /**
   * Application client secret.
   *
   * @var string
   */
  protected $appClientSecret;

  /**
   * The default access token.
   *
   * @var string
   */
  protected $defaultToken;

  /**
   * The redirect URL for social_auth implmeneter.
   *
   * @var string
   */
  protected $oauthRedirectUrl;

  /**
   * {@inheritdoc}
   */
  public function getAppServerUrl() {
    if (!$this->appServerUrl) {
      $this->appServerUrl = $this->config->get('app_server_url');
    }
    return $this->appServerUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppRealm() {
    if (!$this->appRealm) {
      $this->appRealm = $this->config->get('app_realm');
    }
    return $this->appRealm;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppClientId() {
    if (!$this->appClientId) {
      $this->appClientId = $this->config->get('app_client_id');
    }
    return $this->appClientId;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppClientSecret() {
    if (!$this->appClientSecret) {
      $this->appClientSecret = $this->config->get('app_client_secret');
    }
    return $this->appClientSecret;
  }

}
