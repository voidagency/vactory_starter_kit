<?php

namespace Drupal\vactory_content_access\JsonapiAccess;

use Drupal\content_moderation\Access\LatestRevisionCheck;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Access\EntityAccessChecker as CoreEntityAccessChecker;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\vactory_content_access\Services\VactoryNodeAccessCheck;
use Symfony\Component\Routing\RouterInterface;

/**
 * Core entity access checker override.
 */
class EntityAccessChecker extends CoreEntityAccessChecker {

  /**
   * Core entity access checker.
   *
   * @var \Drupal\jsonapi\Access\EntityAccessChecker
   */
  protected $coreEntityAccessChecker;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Vactory node access manager service.
   *
   * @var \Drupal\vactory_content_access\Services\VactoryNodeAccessCheck
   */
  protected $vactoryNodeAccessManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * vactory content access module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $contentAccessConfig;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    CoreEntityAccessChecker $coreEntityAccessChecker,
    ResourceTypeRepositoryInterface $resource_type_repository,
    RouterInterface $router, AccountInterface $account,
    EntityRepositoryInterface $entity_repository,
    LatestRevisionCheck $latestRevisionCheck = NULL,
    EntityTypeManagerInterface $entityTypeManager,
    VactoryNodeAccessCheck $vactoryNodeAccessManager,
    ConfigFactoryInterface $configFactory,
    CurrentRouteMatch $currentRouteMatch
  ) {
    $this->coreEntityAccessChecker = $coreEntityAccessChecker;
    parent::__construct($resource_type_repository, $router, $account, $entity_repository);
    $this->entityTypeManager = $entityTypeManager;
    $this->vactoryNodeAccessManager = $vactoryNodeAccessManager;
    $this->configFactory = $configFactory;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->contentAccessConfig = $configFactory->get('vactory_content_access.settings');
    if (isset($latestRevisionCheck)) {
      $this->setLatestRevisionCheck($latestRevisionCheck);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function checkEntityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkEntityAccess($entity, $operation, $account);
    if ($operation === 'view') {
      $entity_type_id = $entity->getEntityTypeId();
      if ($entity_type_id !== 'node') {
        return $access;
      }
      $bundle = $entity->bundle();
      $is_manage_access_enabled = $this->contentAccessConfig->get($bundle . '_content_type');
      if ($is_manage_access_enabled) {
        $current_user = $this->entityTypeManager->getStorage('user')->load(\Drupal::currentUser()->id());
        // Get the entity access from vactory access content.
        $vactory_content_access = $this->vactoryNodeAccessManager->isAccessible(
          $entity,
          [$entity->bundle()],
          'field_content_access_groups',
          'field_content_access_users',
          'field_content_access_roles',
          $current_user
        );
        if ($vactory_content_access !== NULL) {
          // Not neutral access case.
          $route_name = $this->currentRouteMatch->getRouteName();
          $jasonapi_individual_route = 'jsonapi.node--' . $bundle . '.individual';
          $access = AccessResult::allowedIf($vactory_content_access === TRUE && $access->isAllowed());
          if ($route_name === $jasonapi_individual_route && !$access->isAllowed()) {
            // Jsonapi view individual entity case.
            throw new EntityAccessDeniedHttpException($entity, $access, '');
          }
          return $access;
        }
      }
    }
    return $access;
  }
}