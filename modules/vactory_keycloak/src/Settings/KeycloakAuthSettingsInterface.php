<?php

namespace Drupal\vactory_keycloak\Settings;

use Drupal\social_auth\Settings\SettingsInterface;

/**
 * Defines the settings interface.
 */
interface KeycloakAuthSettingsInterface extends SettingsInterface {

  /**
   * Gets the application server URL.
   *
   * @return string|null
   *   The server URL.
   */
  public function getAppServerUrl(): ?string;

  /**
   * Gets the application realm.
   *
   * @return string|null
   *   The application realm.
   */
  public function getAppRealm(): ?string;

  /**
   * Whether the server certificate verification is disabled.
   *
   * @return bool
   *   TRUE to skip the certificate verification.
   */
  public function noCertVerification(): bool;

}
