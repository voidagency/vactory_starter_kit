<?php

namespace Drupal\vactory_api_key_auth\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks if API Key authentication credentials are given and correct.
 */
class ApiKeyAccessCheck implements AccessInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ApiKeyAccessCheck constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   */
  public function __construct(RequestStack $requestStack, EntityTypeManagerInterface $entity_type_manager) {
    $this->request = $requestStack->getCurrentRequest();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access for api key protected routes.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $key = $this->getKey($this->request);
    if (!$key) {
      return AccessResult::forbidden();
    }

    if (!$this->authenticate($key)) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function authenticate($key) {
    // Load config entity.
    $api_key_entities = \Drupal::entityTypeManager()
      ->getStorage('api_key')
      ->loadByProperties(['key' => $key]);

    return reset($api_key_entities);
  }

  /**
   * Retrieve key from request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that the service will respond to.
   *
   * @return bool
   *   True if api key is present
   */
  public function getKey(Request $request) {
    $form_api_key = $request->get('api_key');

    if (!empty($form_api_key)) {
      return $form_api_key;
    }

    $query_api_key = $request->query->get('api_key');
    if (!empty($query_api_key)) {
      return $query_api_key;
    }

    $header_api_key = $request->headers->get('apikey');
    if (!empty($header_api_key)) {
      return $header_api_key;
    }
    return FALSE;
  }

}
