<?php

namespace Drupal\vactory_user_guide\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A YouTube provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_user_guide_tours",
 *   title = @Translation("User Guide Tours")
 * )
 */
class UserGuideTours extends VactoryDynamicFieldPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_user_guide') . '/widgets');
  }

}
