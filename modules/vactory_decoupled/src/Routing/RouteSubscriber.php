<?php

namespace Drupal\vactory_decoupled\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      // We're only interested in jsonapi resources routes.
      if (strpos($route_name, 'jsonapi.') === 0 && $resource_type = $route->getDefault('resource_type')) {
        // Load resource config if exist.
        $resource_config = $this->entityTypeManager->getStorage('jsonapi_resource_config')
          ->loadByProperties(['resourceType' => $resource_type]);
        if (!empty($resource_config)) {
          $resource_config = reset($resource_config);
          // Get resource config authorized roles.
          $collection_roles = $resource_config->getThirdPartySetting('vactory_decoupled', 'collection_roles', []);
          $collection_roles = array_filter($collection_roles);
          // Allow access to collection endpoint.
          if (str_ends_with($route_name, ".collection") && !empty($collection_roles)) {
            $this->addRolesToJsonApiEndPoint($route, $collection_roles);
          }

          $individual_roles = $resource_config->getThirdPartySetting('vactory_decoupled', 'individual_roles', []);
          $individual_roles = array_filter($individual_roles);
          // Allow access to individual resource (GET, POST, PATCH, DELETE).
          if (!str_ends_with($route_name, ".collection") && !empty($individual_roles)) {
            $this->addRolesToJsonApiEndPoint($route, $individual_roles);
          }
        }
      }
    }
    if ($route = $collection->get('simple_oauth.userinfo')) {
      $route->setDefault('_controller', 'Drupal\vactory_decoupled\Controller\UserInfo::handle');
    }
  }

  /**
   * Allow access to json api endpoint per roles.
   */
  protected function addRolesToJsonApiEndPoint(&$route, $roles) {
    $requirements = $route->getRequirements();
    $requirements['_role'] = implode('+', $roles);
    $route->setRequirements($requirements);
  }

}
