<?php

namespace Drupal\vactory_checklist;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager for Checklist plugins.
 */
class ChecklistPluginManager extends DefaultPluginManager {

  /**
   * Constructs a ChecklistPluginManager object.
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
    parent::__construct(
      'Plugin/Checklist',
      $namespaces,
      $module_handler,
      'Drupal\vactory_checklist\Plugin\Checklist\ChecklistInterface',
      'Drupal\vactory_checklist\Annotation\ChecklistPlugin'
    );

    $this->alterInfo('vactory_checklist_info');
    $this->setCacheBackend($cache_backend, 'vactory_checklist_plugins');
  }

}
