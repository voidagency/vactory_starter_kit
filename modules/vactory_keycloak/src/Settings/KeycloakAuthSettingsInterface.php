<?php

namespace Drupal\vactory_keycloak\Settings;

/**
 * Defines the settings interface.
 */
interface KeycloakAuthSettingsInterface {

  /**
   * Gets the application server URL.
   *
   * @return mixed
   *   The server URL.
   */
  public function getAppServerUrl();

  /**
   * Gets the application realm.
   *
   * @return string
   *   The application realm.
   */
  public function getAppRealm();

  /**
   * Gets the application client id.
   *
   * @return string
   *   The application client id.
   */
  public function getAppClientId();

  /**
   * Gets the application client secret.
   *
   * @return string
   *   The application client secret.
   */
  public function getAppClientSecret();


}
