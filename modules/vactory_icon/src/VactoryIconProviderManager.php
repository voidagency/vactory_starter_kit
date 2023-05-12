<?php

namespace Drupal\vactory_icon;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for vactory icon provider plugins.
 */
class VactoryIconProviderManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/VactoryIconProvider';
    $plugin_interface = 'Drupal\vactory_icon\VactoryIconProviderInterface';

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = 'Drupal\vactory_icon\Annotation\VactoryIconProvider';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    // This allows the plugin definitions to be altered by an alter hook.
    $this->alterInfo('vactory_icon_provider_info');

    // This sets the caching method for our plugin definitions.
    $this->setCacheBackend($cache_backend, 'vactory_icon_provider_info');
  }

}
