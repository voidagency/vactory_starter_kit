<?php

namespace Drupal\vactory_decoupled_search_ai\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A Dynamic Field Sliders provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_dynamic_search_ai",
 *   title = @Translation("Vactory dynamic search ai")
 * )
 */
class VactoryDynamicSearchAi extends VactoryDynamicFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_decoupled_search_ai') . '/widgets');
  }

}
