<?php

namespace Drupal\vactory_push_notification;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\vactory_push_notification\Annotation\PushService;

/**
 * A plugin manager for push service plugins.
 */
class PushServicesPluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/PushServices';

    $plugin_interface = PushServiceInterface::class;

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = PushService::class;

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    // see vactory_push_notification_push_service_info_alter().
    $this->alterInfo('push_service_info');

    $this->setCacheBackend($cache_backend, 'push_service_info', ['push_service_info']);
  }

}
