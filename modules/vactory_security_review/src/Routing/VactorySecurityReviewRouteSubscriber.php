<?php

namespace Drupal\vactory_security_review\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Vactory security review event subscriber.
 */
class VactorySecurityReviewRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('security_review.settings');
    if ($route) {
      $route->setDefault('_form', '\Drupal\vactory_security_review\Form\SettingsForm');
    }
  }

}
