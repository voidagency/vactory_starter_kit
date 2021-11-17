<?php

namespace Drupal\vactory_dynamic_field_nodes\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A Map provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_dynamic_field_nodes",
 *   title = @Translation("Dynamic Field Nodes")
 * )
 */
class DynamicFieldMap extends VactoryDynamicFieldPluginBase {

  /**
   * DynamicFieldMap constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, drupal_get_path('module', 'vactory_dynamic_field_nodes') . '/widgets');
  }

}
