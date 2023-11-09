<?php

namespace Drupal\vactory_jsonapi_extras\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\vactory_jsonapi_extras\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('jsonapi.resource_list')) {
      $requirements = $route->getRequirements();
      $requirements['_role'] = 'administrator';
      $route->setRequirements($requirements);
    }
  }

}
