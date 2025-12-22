<?php

namespace Drupal\vactory_diff_config_client\Commands;

use Drupal\vactory_diff_config_client\Service\ConfigComparisonService;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Vactory Config Comparison module.
 */
class ConfigCompareCommands extends DrushCommands {

  /**
   * Config comparison service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\ConfigComparisonService
   */
  protected $comparisonService;

  /**
   * Constructs a new ConfigCompareCommands object.
   */
  public function __construct(ConfigComparisonService $comparison_service) {
    parent::__construct();
    $this->comparisonService = $comparison_service;
  }

  /**
   * Compare configurations between local and remote instances.
   *
   * @command vactory_diff_config_compare
   * @aliases vf-compare
   * @usage drush vactory_diff_config_compare
   *   Compare configurations from the configured remote URL.
   */
  public function compare() {
    $this->output()->writeln('Starting configuration comparison...');

    // Use the service method that orchestrates the entire process.
    $result = $this->comparisonService->generateDiffReport();

    if (!$result['success']) {
      $this->logger()->error($result['message']);
      return;
    }

    // Display summary.
    $this->output()->writeln('');
    $this->output()->writeln('Comparison complete!');
    $this->output()->writeln('Remote URL: ' . $result['remote_url']);
    $this->output()->writeln('');

    // Get summary from results.
    if (!empty($result)) {
      $this->output()->writeln('Summary:');
      $this->output()->writeln('- Added configurations: ' . ($result['added_count'] ?? 0));
      $this->output()->writeln('- Modified configurations: ' . ($result['modified_count'] ?? 0));
      $this->output()->writeln('- Deleted configurations: ' . ($result['deleted_count'] ?? 0));
    }
    else {
      $this->output()->writeln('No differences found.');
    }

    $this->output()->writeln('');
    $this->output()->writeln('Results saved to: private://config-diff/report.json');
    $this->output()->writeln('View TODO list at: /admin/config/development/vactory-diff/todo');
  }

}
