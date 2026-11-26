<?php

namespace Drupal\vactory_api_key_auth\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\vactory_api_key_auth\Authentication\Provider\ApiKeyAuth;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Access check "_route_api_key_auth__access_check".
 *
 * Grants access when the "apikey" header holds a key linked to an active
 * account.
 */
class ApiKeyAccessCheck implements AccessInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an ApiKeyAccessCheck object.
   */
  public function __construct(RequestStack $requestStack, EntityTypeManagerInterface $entity_type_manager) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access.
   */
  public function access() {
    $request = $this->requestStack->getCurrentRequest();
    $key = $request ? ApiKeyAuth::keyFromRequest($request) : FALSE;
    if ($key === FALSE) {
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }
    foreach ($this->entityTypeManager->getStorage('api_key')->loadMultiple() as $api_key) {
      if (is_string($api_key->key) && $api_key->key !== '' && hash_equals($api_key->key, $key)) {
        $account = $this->entityTypeManager->getStorage('user')->load($api_key->user_uuid);
        if ($account && !$account->isBlocked()) {
          return AccessResult::allowed()->setCacheMaxAge(0);
        }
        break;
      }
    }
    return AccessResult::forbidden()->setCacheMaxAge(0);
  }

}
