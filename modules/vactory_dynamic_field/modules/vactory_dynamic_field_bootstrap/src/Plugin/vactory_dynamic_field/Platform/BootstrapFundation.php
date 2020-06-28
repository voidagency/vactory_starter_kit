<?php

namespace Drupal\vactory_dynamic_field_bootstrap\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A YouTube provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_bootstrap",
 *   title = @Translation("Bootstrap")
 * )
 */
class BootstrapFundation extends VactoryDynamicFieldPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, drupal_get_path('module', 'vactory_dynamic_field_bootstrap') . '/widgets');
  }

}