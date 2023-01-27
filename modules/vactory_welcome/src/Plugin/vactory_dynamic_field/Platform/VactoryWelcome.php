<?php

namespace Drupal\vactory_welcome\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * Vactory welcome DF plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_welcome",
 *   title = @Translation("Vactory Welcome")
 * )
 */
class VactoryWelcome extends VactoryDynamicFieldPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_welcome') . '/widgets');
  }

}