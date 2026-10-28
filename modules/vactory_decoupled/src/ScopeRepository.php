<?php

namespace Drupal\vactory_decoupled;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

/**
 * Add the authenticated scope to user tokens requested without scopes.
 *
 * Simple OAuth 5.x always added the authenticated role scope, 6.x returns
 * an empty scope list, keep the old token "scope" claim for the frontend.
 */
class ScopeRepository implements ScopeRepositoryInterface {

  /**
   * User grant types getting the authenticated scope by default.
   */
  const USER_GRANT_TYPES = ['password', 'faceid', 'apikey'];

  /**
   * The decorated scope repository.
   *
   * @var \League\OAuth2\Server\Repositories\ScopeRepositoryInterface
   */
  protected $inner;

  /**
   * Class constructor.
   */
  public function __construct(ScopeRepositoryInterface $inner) {
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface {
    return $this->inner->getScopeEntityByIdentifier($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function finalizeScopes(array $scopes, string $grantType, ClientEntityInterface $clientEntity, string|null $userIdentifier = NULL, ?string $authCodeId = NULL): array {
    $finalized_scopes = $this->inner->finalizeScopes($scopes, $grantType, $clientEntity, $userIdentifier, $authCodeId);
    if (!empty($finalized_scopes) || empty($userIdentifier) || !in_array($grantType, static::USER_GRANT_TYPES, TRUE)) {
      return $finalized_scopes;
    }

    $authenticated = $this->inner->getScopeEntityByIdentifier('authenticated');
    if ($authenticated) {
      $finalized_scopes[] = $authenticated;
    }
    return $finalized_scopes;
  }

}
