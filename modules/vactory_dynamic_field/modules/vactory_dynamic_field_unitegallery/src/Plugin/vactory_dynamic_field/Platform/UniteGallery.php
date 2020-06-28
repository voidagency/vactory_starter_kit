<?php

namespace Drupal\vactory_dynamic_field_unitegallery\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * An Unite Gallery provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_unitegallery",
 *   title = @Translation("UniteGallery")
 * )
 */
class UniteGallery extends VactoryDynamicFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, drupal_get_path('module', 'vactory_dynamic_field_unitegallery') . '/widgets');
  }

}
