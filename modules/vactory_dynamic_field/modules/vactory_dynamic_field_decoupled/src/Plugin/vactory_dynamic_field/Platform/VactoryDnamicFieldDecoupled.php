<?php

namespace Drupal\vactory_dynamic_field_decoupled\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A YouTube provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_dynamic_field_decoupled",
 *   title = @Translation("dynamicFieldDecoupled")
 * )
 */
class VactoryDnamicFieldDecoupled extends VactoryDynamicFieldPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_dynamic_field_decoupled') . '/widgets');
  }

}
