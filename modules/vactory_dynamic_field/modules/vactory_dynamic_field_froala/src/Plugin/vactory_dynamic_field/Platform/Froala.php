<?php

namespace Drupal\vactory_dynamic_field_froala\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A YouTube provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_froala",
 *   title = @Translation("Froala")
 * )
 */
class Froala extends VactoryDynamicFieldPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, drupal_get_path('module', 'vactory_dynamic_field_froala') . '/widgets');
  }

}