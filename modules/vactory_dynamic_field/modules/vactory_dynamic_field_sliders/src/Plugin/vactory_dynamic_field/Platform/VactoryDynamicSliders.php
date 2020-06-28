<?php

namespace Drupal\vactory_dynamic_field_sliders\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A Dynamic Field Sliders provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_dynamic_sliders",
 *   title = @Translation("Sliders")
 * )
 */
class VactoryDynamicSliders extends VactoryDynamicFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, drupal_get_path('module', 'vactory_dynamic_field_sliders') . '/widgets');
  }

}
