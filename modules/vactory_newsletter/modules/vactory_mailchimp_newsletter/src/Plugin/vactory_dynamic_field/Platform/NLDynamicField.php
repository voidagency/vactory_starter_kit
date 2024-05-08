<?php

namespace Drupal\vactory_mailchimp_newsletter\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;

/**
 * A NL provider plugin.
 *
 * @PlatformProvider(
 *   id = "nl_templates",
 *   title = @Translation("Newsletter templates")
 * )
 */
class NLDynamicField extends VactoryDynamicFieldPluginBase {

  /**
   * NLDynamicField constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_mailchimp_newsletter') . '/widgets');
  }

}
