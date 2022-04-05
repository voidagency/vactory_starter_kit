<?php

namespace Drupal\vactory_decoupled\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('simple_oauth.userinfo')) {
      $route->setDefault('_controller', 'Drupal\vactory_decoupled\Controller\UserInfo::handle');
    }
  }

}