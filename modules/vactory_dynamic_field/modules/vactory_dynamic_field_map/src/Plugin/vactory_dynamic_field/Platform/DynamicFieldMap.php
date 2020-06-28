<?php

namespace Drupal\vactory_dynamic_field_map\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A Map provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_dynamic_field_map",
 *   title = @Translation("Dynamic Field Map")
 * )
 */
class DynamicFieldMap extends VactoryDynamicFieldPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, drupal_get_path('module', 'vactory_dynamic_field_map') . '/widgets');
  }

}
