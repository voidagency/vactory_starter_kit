<?php

namespace Drupal\vactory_console_config\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for vactory_console_config routes.
 */
class VactoryConsoleConfigController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {
    $results = [];
    $modules = \Drupal::config('vactory_console_config.settings');
    if (!empty($modules->get('modules'))) {
      $results = json_decode($modules->get('modules'), TRUE);
    }
    
    return [
      '#theme' => 'vactory_console_config_overview',
      '#modules' => $results,
    ];
  }

}
