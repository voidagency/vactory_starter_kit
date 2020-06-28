<?php

namespace Drupal\vactory_espace_prive\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class EspacePriveRouteSubscriber.
 *
 * @package Drupal\vactory_espace_prive\Routing
 */
class EspacePriveRouteSubscriber extends RouteSubscriberBase {

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function alterRoutes(RouteCollection $collection) {
    $espace_prive_settings = \Drupal::config('vactory_espace_prive.settings');
    if ($route = $collection->get('vactory_espace_prive.welcome')) {
      $path_welcome = $espace_prive_settings->get('path_welcome');
      $path_welcome = strpos($path_welcome, '/') !== 0 ? '/' . $path_welcome : $path_welcome;
      $route->setPath($path_welcome);
    }
    if ($route = $collection->get('user.login')) {
      $path_login = $espace_prive_settings->get('path_login');
      $path_login = strpos($path_login, '/') !== 0 ? '/' . $path_login : $path_login;
      $route->setPath($path_login);
      $route_espace_prive = $collection->get('vactory_espace_prive.login');
      $route_espace_prive->setPath($path_login);
    }
    if (($route = $collection->get('user.register')) || ($route = $collection->get('vactory_espace_prive.register'))) {
      $path_register = $espace_prive_settings->get('path_register');
      $path_register = strpos($path_register, '/') !== 0 ? '/' . $path_register : $path_register;
      $route->setPath($path_register);
      $route_espace_prive = $collection->get('vactory_espace_prive.register');
      $route_espace_prive->setPath($path_register);
    }
    if (($route = $collection->get('user.pass')) || ($route = $collection->get('vactory_espace_prive.password'))) {
      $path_password = $espace_prive_settings->get('path_password');
      $path_password = strpos($path_password, '/') !== 0 ? '/' . $path_password : $path_password;
      $route->setPath($path_password);
      $route_espace_prive = $collection->get('vactory_espace_prive.password');
      $route_espace_prive->setPath($path_password);
    }
  }

}
