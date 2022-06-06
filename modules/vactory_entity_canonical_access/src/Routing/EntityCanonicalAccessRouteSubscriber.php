<?php

namespace Drupal\vactory_entity_canonical_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class EntityCanonicalAccessRouteSubscriber.
 *
 * @package Drupal\vactory_entity_canonical_access\Routing
 */
class EntityCanonicalAccessRouteSubscriber extends RouteSubscriberBase {

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function alterRoutes(RouteCollection $collection) {
    $settings = \Drupal::config('vactory_entity_canonical_access.settings');
    $content_entities = $settings->get('content_entities');
    if (!empty($content_entities)) {
      foreach ($content_entities as $entity_id => $config) {
        $canonical_route = 'entity.' . $entity_id . '.canonical';
        if ($config['policy'] !== 'default' && $route = $collection->get($canonical_route)) {
          $roles = !empty($config['roles']) ? implode('+', $config['roles']) : '';
          if (!empty($roles)) {
            $route->setRequirement('_role', $roles);
          }
          else {
            $route->setRequirement('_access', 'FALSE');
          }
        }
      }
    }
  }

}
