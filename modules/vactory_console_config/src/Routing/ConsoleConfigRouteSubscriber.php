<?php

namespace Drupal\vactory_console_config\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class EspacePriveRouteSubscriber.
 *
 * @package Drupal\vactory_espace_prive\Routing
 */
class ConsoleConfigRouteSubscriber extends RouteSubscriberBase {

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function alterRoutes(RouteCollection $collection) {
    $modules = [];
    $extension_module = \Drupal::service("extension.list.module");
    foreach ($collection as $key => $route) {
      $defaults = $route->getDefaults();
      $requirements = $route->getRequirements();
      if (isset($defaults['_form']) && (str_starts_with($defaults['_form'], '\Drupal\vactory_') || str_starts_with($defaults['_form'], 'Drupal\vactory_')) && 
        is_subclass_of($defaults['_form'], '\Drupal\Core\Form\ConfigFormBase')) {
          $pattern = '/\\\\?Drupal\\\\([^\\\\]+)\\\\(Form|Plugin\\\\Form)\\\\/';
          preg_match($pattern, $defaults['_form'], $matches);
          $module_name = !empty($matches) ? $matches[1] : '';
          $info = $extension_module->getExtensionInfo($module_name);
          $modules[$module_name]['moduleName'] = $info['name'];
          $modules[$module_name]['moduleDescription'] = $info['description'] ?? '';
          $modules[$module_name]['settings'][] = [
            'title' => $defaults['_title'],
            'permission' => $requirements['_permission'] ?? '',
            'path' => $route->getPath(),
          ];
      }
    }
    if (!empty($modules)) {
      $config = \Drupal::service('config.factory')->getEditable('vactory_console_config.settings');
      $config->set('modules', json_encode($modules))->save();
    }
  }

}
