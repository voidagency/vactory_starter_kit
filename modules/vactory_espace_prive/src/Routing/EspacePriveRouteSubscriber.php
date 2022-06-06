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
    if ($route = $collection->get('entity.user.canonical')) {
      $path_welcome = $espace_prive_settings->get('path_welcome');
      $path_welcome = strpos($path_welcome, '/') !== 0 ? '/' . $path_welcome : $path_welcome;
      $path_user_view = $path_welcome . '/{user}';
      $route->setPath($path_user_view);
      // Route Requirements.
      $requirements = $route->getRequirements();
      $requirements['_access'] = 'TRUE';
      unset($requirements['_entity_access']);
      $route->setRequirements($requirements);

      // Route Defaults.
      $defaults = [
        '_controller' => '\Drupal\vactory_espace_prive\Controller\EspacePriveController::userView',
        '_title' => 'Détails de profil',
      ];
      $route->setDefaults($defaults);

      // Route Options.
      $options = $route->getOptions();
      $options['parameters']['user']['type'] = 'entity:user';
      $route->setOptions($options);

      $route_espace_prive_user_view = $collection->get('vactory_espace_prive.user_view');
      $route_espace_prive_user_view->setPath($path_user_view);

      $route_espace_prive_welcome = $collection->get('vactory_espace_prive.welcome');
      $route_espace_prive_welcome->setPath($path_welcome);
    }
    if ($route = $collection->get('user.login')) {
      $path_login = $espace_prive_settings->get('path_login');
      $path_login = strpos($path_login, '/') !== 0 ? '/' . $path_login : $path_login;
      $route->setPath($path_login);
      $route_espace_prive = $collection->get('vactory_espace_prive.login');
      $route_espace_prive->setPath($path_login);
    }
    if ($route = $collection->get('user.login.http')) {
      $path_login = $espace_prive_settings->get('path_login');
      $path_login = strpos($path_login, '/') !== 0 ? '/' . $path_login : $path_login;
      $route->setPath($path_login);
      $route_espace_prive = $collection->get('vactory_espace_prive.login');
      $route_espace_prive->setMethods(['POST']);
      $route_espace_prive->setPath($path_login);
    }
    if ($route = $collection->get('user.register')) {
      $path_register = $espace_prive_settings->get('path_register');
      $path_register = strpos($path_register, '/') !== 0 ? '/' . $path_register : $path_register;
      $route->setPath($path_register);
      $route_espace_prive = $collection->get('vactory_espace_prive.register');
      $route_espace_prive->setPath($path_register);
    }
    if ($route = $collection->get('user.pass')) {
      // Route Path.
      $path_password = $espace_prive_settings->get('path_password');
      $path_password = strpos($path_password, '/') !== 0 ? '/' . $path_password : $path_password;
      $route->setPath($path_password);

      // Route Defaults.
      $defaults = [
        '_controller' => '\Drupal\vactory_espace_prive\Controller\EspacePriveController::resetPassword',
        '_title' => 'Mon profil',
      ];
      $route->setDefaults($defaults);

      $route_espace_prive = $collection->get('vactory_espace_prive.password');
      $route_espace_prive->setPath($path_password);
    }
    if ($route = $collection->get('entity.user.edit_form')) {
      $path_profile_settings = $espace_prive_settings->get('path_profile');
      $path_profile_settings = strpos($path_profile_settings, '/') !== 0 ? '/' . $path_profile_settings : $path_profile_settings;
      $path_profile = $path_profile_settings . '/{user}';
      $route->setPath($path_profile);

      // Route Requirements.
      $requirements = $route->getRequirements();
      $requirements['_access'] = 'TRUE';
      unset($requirements['_entity_access']);
      $route->setRequirements($requirements);

      // Route Defaults.
      $defaults = [
        '_controller' => '\Drupal\vactory_espace_prive\Controller\EspacePriveController::profile',
        '_title' => 'Mon profil',
      ];
      $route->setDefaults($defaults);

      // Route Options.
      $options = $route->getOptions();
      $options['parameters']['user']['type'] = 'entity:user';
      $route->setOptions($options);

      $route_espace_prive_profile = $collection->get('vactory_espace_prive.profile');
      $route_espace_prive_profile->setPath($path_profile);

      $route_espace_prive_cleaned_profile = $collection->get('vactory_espace_prive.cleaned_profile');
      $route_espace_prive_cleaned_profile->setPath($path_profile_settings);
    }
  }

}
