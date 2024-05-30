<?php

namespace Drupal\vactory_flood_control\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class VactoryFloodControlRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('flood_control.settings')) {
      $route->setDefault('_form', 'Drupal\vactory_flood_control\Form\VactoryFloodControlSettingsForm');
    }
  }

}
