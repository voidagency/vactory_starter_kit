<?php

namespace Drupal\vactory_dynamic_field\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $isDropdownSelectMode = \Drupal::config('vactory_dynamic_field.settings')
      ->get('pending_content');
    if ($isDropdownSelectMode) {
      $this->derivatives['df.pending_content'] = $base_plugin_definition;
      $this->derivatives['df.pending_content']['title'] = "🕒 Pending Content";
      $this->derivatives['df.pending_content']['route_name'] = 'df_pending_content.dashboard';
      $this->derivatives['df.pending_content']['base_route'] = 'system.admin_content';
      return parent::getDerivativeDefinitions($base_plugin_definition);
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
