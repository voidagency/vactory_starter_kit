<?php

namespace Drupal\vactory_notifications\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A YouTube provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_notifications_df",
 *   title = @Translation("Vactory Notifications Dynamic Fields")
 * )
 */
class VactoryNotificationsDynamicField extends VactoryDynamicFieldPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_notifications') . '/widgets');
  }

}
