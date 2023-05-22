<?php

namespace Drupal\vactory_business\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * Fiche business DF provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_business",
 *   title = @Translation("Fiche Business")
 * )
 */
class FicheBusiness extends VactoryDynamicFieldPluginBase {

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_business') . '/widgets');
  }

}
