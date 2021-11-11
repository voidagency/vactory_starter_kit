<?php

namespace Drupal\vactory_reminder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Reminder plugin manager.
 *
 * @see \Drupal\vactory_reminder\Annotation\Reminder
 * @see \Drupal\vactory_reminder\ReminderInterface
 * @see plugin_api
 */
class ReminderManager extends DefaultPluginManager {

  /**
   * Constructs a ReminderManager object.
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
      'Plugin/Reminder',
      $namespaces,
      $module_handler,
      'Drupal\vactory_reminder\ReminderInterface',
      'Drupal\vactory_reminder\Annotation\Reminder'
    );
    $this->alterInfo('reminder_info');
    $this->setCacheBackend($cache_backend, 'reminder_info_plugins');
  }

}
