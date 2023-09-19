<?php

namespace Drupal\vactory_dynamic_field_last_nodes\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A Map provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_dynamic_field_nodes_last",
 *   title = @Translation("Dynamic Field Nodes - Last nodes")
 * )
 */
class DynamicFieldMap extends VactoryDynamicFieldPluginBase {

  /**
   * DynamicFieldMap constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_dynamic_field_last_nodes') . '/widgets');
  }

}
