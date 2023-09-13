<?php

namespace Drupal\vactory_jsonapi_extras\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Routing\Routes as JsonapiRoutes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 */
class Routes extends JsonapiRoutes {

  /**
   * Cloned resource type repository service.
   *
   * @var \Drupal\jsonapi_cloned_resource_type\ResourceType\ClonedResourceTypeRepository
   */
  protected $clonedResourceTypeRespository;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function routes() {
    $routes = new RouteCollection();
    $exposed_apis = $this->entityTypeManager->getStorage('exposed_apis')
      ->loadByProperties([
        'status' => 1
      ]);

    $original_resources = array_map(function ($entity) {
      return $entity->originalResource();
    }, $exposed_apis);

    $resource_types = array_filter($this->resourceTypeRepository->all(), function ($resourceType) use ($original_resources) {
      $name = $resourceType->getEntityTypeId() . ResourceType::TYPE_NAME_URI_PATH_SEPARATOR . $resourceType->getBundle();
      return in_array($name, $original_resources, TRUE);
    });
    foreach ($exposed_apis as $exposed_api) {
      if (!$exposed_api->isCustomResource()) {
        $original_resource = $exposed_api->originalResource();
        [$entity_type_id, $bundle] = explode(ResourceType::TYPE_NAME_URI_PATH_SEPARATOR, $original_resource);

        foreach ($resource_types as $resource_type) {
          if ($resource_type->getBundle() === $bundle && $resource_type->getEntityTypeId() === $entity_type_id) {
            $route = static::getRoutesForResourceType($resource_type, $this->jsonApiBasePath);
            $resource_routes = $route->all();
            if (!empty($resource_routes)) {
              $suffix = ".collection";
              $length = strlen($suffix);
              $collection_resource_routes = array_filter($resource_routes, function ($key) use ($suffix, $length) {
                return substr($key, -$length) === $suffix;
              }, ARRAY_FILTER_USE_KEY);
              if (!empty($collection_resource_routes)) {
                /** @var Route $resource_route */
                $resource_route = reset($collection_resource_routes);
                $resource_route = clone $resource_route;
                $requirements = $resource_route->getRequirements();
                $path = $exposed_api->path();
                $id = $exposed_api->id();
                $this->checkRouteAccess($exposed_api, $requirements);
                $requirements['_format'] = 'json';
                $resource_route->setRequirements($requirements);
                $resource_route->setDefault('exposed_api', $id);
                $resource_route->setPath($path);
                $resource_route->setDefault(JsonapiRoutes::JSON_API_ROUTE_FLAG_KEY, TRUE);
                $routes->add("exposed_api.{$id}", $resource_route);
              }
            }
          }
        }
      }
      else {
        $path = $exposed_api->path();
        $resource_route = new Route($path);
        $id = $exposed_api->id();
        $requirements = [
          "_access" => "TRUE",
          "_format" => "json",
        ];
        $this->checkRouteAccess($exposed_api, $requirements);
        $resource_route->setRequirements($requirements);
        $resource_route->setDefault('_controller', $exposed_api->getCustomController());
        $resource_route->setDefault('exposed_api', $id);
        $resource_route->setDefault(JsonapiRoutes::JSON_API_ROUTE_FLAG_KEY, TRUE);
        $routes->add("exposed_api.{$id}", $resource_route);
      }
    }

    return $routes;
  }

  /**
   * Check for route requirement access.
   */
  protected function checkRouteAccess($route, &$requirements) {
    $route_packages = $route->packages();
    $roles = [];
    if (!empty($route_packages)) {
      $packages = $this->entityTypeManager->getStorage('api_package')
        ->loadMultiple($route_packages);
      if (!empty($packages)) {
        foreach ($packages as $package) {
          $package_roles = !empty($package->roles()) ? $package->roles() : [];
          $package_roles = array_filter($package_roles, function ($role) {return $role;});
          $roles = array_merge($roles, $package_roles);
        }
      }
      if (!empty($roles)) {
        $requirements['_role'] = implode('+', $roles);
      }
    }
  }

}
