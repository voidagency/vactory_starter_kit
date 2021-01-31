<?php

namespace Drupal\vactory_dynamic_field_juicer\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A Juicer plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_juicer",
 *   title = @Translation("juicer")
 * )
 */
class Juicer extends VactoryDynamicFieldPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, drupal_get_path('module', 'vactory_dynamic_field_juicer') . '/widgets');
  }

}
