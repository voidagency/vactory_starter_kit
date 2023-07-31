<?php

namespace Drupal\vactory_taxonomy_results;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Term result counter plugin manager.
 */
class TermResultCounterManager extends DefaultPluginManager {

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/TermResultCounter',
      $namespaces,
      $module_handler,
      'Drupal\vactory_taxonomy_results\TermResultCounterInterface',
      'Drupal\vactory_taxonomy_results\Annotation\TermResultCounter'
    );
    $this->alterInfo('term_result_counter_info');
    $this->setCacheBackend($cache_backend, 'term_result_counter_info_plugins');
  }

}
