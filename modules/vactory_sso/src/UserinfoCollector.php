<?php

namespace Drupal\vactory_sso;

/**
 * Keeps the userinfo claims received during the current SSO callback.
 *
 * The claims live in memory for the current PHP request only: nothing is
 * written to the session or to the database.
 */
final class UserinfoCollector {

  /**
   * Claims returned by the userinfo endpoint, or NULL.
   *
   * @var array|null
   */
  private ?array $claims = NULL;

  /**
   * Stores the claims of the identity provider.
   *
   * @param array $claims
   *   Decoded userinfo response.
   */
  public function set(array $claims): void {
    $this->claims = $claims;
  }

  /**
   * Returns the claims received during this request.
   *
   * @return array|null
   *   Claims, or NULL when no userinfo response was received.
   */
  public function get(): ?array {
    return $this->claims;
  }

  /**
   * Forgets the claims.
   */
  public function reset(): void {
    $this->claims = NULL;
  }

}
