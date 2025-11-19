<?php

namespace Drupal\vactory_diff_config_client\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for generating to-do list from configuration differences.
 */
class TodoListGeneratorService {

  /**
   * The feature detection service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\FeatureDetectionService
   */
  protected $featureDetection;

  /**
   * The config comparison service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\ConfigComparisonService
   */
  protected $comparisonService;

  /**
   * The module installation service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\ModuleInstallationService
   */
  protected $moduleInstallation;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a TodoListGeneratorService object.
   *
   * @param \Drupal\vactory_diff_config_client\Service\FeatureDetectionService $feature_detection
   *   The feature detection service.
   * @param \Drupal\vactory_diff_config_client\Service\ConfigComparisonService $comparison_service
   *   The config comparison service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\vactory_diff_config_client\Service\ModuleInstallationService $module_installation
   *   The module installation service.
   */
  public function __construct(
    FeatureDetectionService $feature_detection,
    ConfigComparisonService $comparison_service,
    LoggerChannelFactoryInterface $logger_factory,
    ModuleInstallationService $module_installation
  ) {
    $this->featureDetection = $feature_detection;
    $this->comparisonService = $comparison_service;
    $this->logger = $logger_factory->get('vactory_diff_config_client');
    $this->moduleInstallation = $module_installation;
  }

  /**
   * Generate list from comparison results.
   */
  public function generateTodoList(?array $comparison_results = NULL): array {
    if ($comparison_results === NULL) {
      $comparison_results = $this->comparisonService->loadComparisonResults();
    }

    if ($comparison_results === NULL) {
      return [
        'features' => [],
        'errors' => ['No comparison results found. Please run a comparison first.'],
        'summary' => [
          'total_features' => 0,
          'total_configs' => 0,
        ],
      ];
    }

    $features_to_revert = [];
    $unmatched_configs = [];
    $total_configs_processed = 0;

    // Process added configurations.
    $added_configs = $comparison_results['differences_by_type']['added'] ?? [];
    foreach ($added_configs as $type => $configs) {
      foreach ($configs as $config) {
        $total_configs_processed++;
        $result = $this->processConfig($config['name'], $config['local_data'], 'added');

        if ($result['matched']) {
          $this->addFeatureToList($features_to_revert, $result['module'], $config['name'], 'added', $type);
        }
        else {
          $unmatched_configs[] = [
            'config_name' => $config['name'],
            'type' => $type,
            'change_type' => 'added',
            'reason' => $result['reason'] ?? 'Module not found',
          ];
        }
      }
    }

    // Process modified configurations.
    $modified_configs = $comparison_results['differences_by_type']['modified'] ?? [];
    foreach ($modified_configs as $type => $configs) {
      foreach ($configs as $config) {
        $total_configs_processed++;
        $result = $this->processConfig($config['name'], $config['local_data'], 'modified');

        if ($result['matched']) {
          $this->addFeatureToList($features_to_revert, $result['module'], $config['name'], 'modified', $type);
        }
        else {
          $unmatched_configs[] = [
            'config_name' => $config['name'],
            'type' => $type,
            'change_type' => 'modified',
            'reason' => $result['reason'] ?? 'Module not found or config does not match',
          ];
        }
      }
    }

    // Sort features by name.
    ksort($features_to_revert);

    // Build content sync commands from content diff CSV (added/modified only).
    $content_sync = $this->buildContentSyncCommands();

    return [
      'features' => $features_to_revert,
      'unmatched_configs' => $unmatched_configs,
      'summary' => [
        'total_features' => count($features_to_revert),
        'total_configs' => $total_configs_processed,
        'matched_configs' => $total_configs_processed - count($unmatched_configs),
        'unmatched_configs' => count($unmatched_configs),
      ],
      'content_sync' => $content_sync,
      'timestamp' => $comparison_results['timestamp'] ?? NULL,
    ];
  }

  /**
   * Process a single configuration.
   *
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data.
   * @param string $change_type
   *   The change type: 'added' or 'modified'.
   *
   * @return array
   *   Result with 'matched', 'module', and optional 'reason'.
   */
  protected function processConfig(string $config_name, array $config_data, string $change_type): array {
    // Find which module provides this config.
    $module_info = $this->featureDetection->findModuleForConfig($config_name);

    if ($module_info === NULL) {
      return [
        'matched' => FALSE,
        'reason' => 'Configuration not found in any module',
      ];
    }

    $module_name = $module_info['module'];
    $module_config_data = $module_info['config_data'];

    // Check if the module is a feature.
    if (!$this->featureDetection->isFeature($module_name)) {
      return [
        'matched' => FALSE,
        'module' => $module_name,
        'reason' => 'Module is not a feature (no .features.yml file)',
      ];
    }

    // Verify that the config matches.
    $matches = $this->featureDetection->configMatchesModule(
      $config_data,
      $module_config_data,
      $change_type
    );

    if (!$matches) {
      return [
        'matched' => FALSE,
        'module' => $module_name,
        'reason' => 'Configuration does not match module config',
      ];
    }

    return [
      'matched' => TRUE,
      'module' => $module_name,
    ];
  }

  /**
   * Add a feature to the revert list.
   *
   * @param array &$features_list
   *   The features list (passed by reference).
   * @param string $module_name
   *   The module name.
   * @param string $config_name
   *   The configuration name.
   * @param string $change_type
   *   The change type.
   * @param string $config_type
   *   The configuration type.
   */
  protected function addFeatureToList(array &$features_list, string $module_name, string $config_name, string $change_type, string $config_type): void {
    if (!isset($features_list[$module_name])) {
      $features_list[$module_name] = [
        'module' => $module_name,
        'command' => "drush fr $module_name -y",
        'configs' => [],
        'stats' => [
          'added' => 0,
          'modified' => 0,
        ],
      ];
    }

    $features_list[$module_name]['configs'][] = [
      'name' => $config_name,
      'type' => $config_type,
      'change_type' => $change_type,
    ];

    $features_list[$module_name]['stats'][$change_type]++;
  }

  /**
   * Build content sync commands from CSV report.
   *
   * Scans private://content-diff/report.csv and prepares single_content_sync
   * export/import commands for entities with status 'added' or 'modified'.
   *
   * @return array
   *   Array with 'exports' (per type), 'instructions', 'imports'.
   */
  protected function buildContentSyncCommands(): array {
    $file_uri = 'private://content-diff/report.csv';
    $content_sync = [
      'has_changes' => FALSE,
      'by_type' => [],
      'export_commands' => [],
      'export_dir' => './scs-export',
    ];

    // Attempt to read the CSV.
    try {
      $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
      $real_path = $stream_wrapper_manager->getViaUri($file_uri)->realpath();
      if (!$real_path || !file_exists($real_path)) {
        return $content_sync;
      }

      $handle = fopen($real_path, 'r');
      if (!$handle) {
        return $content_sync;
      }

      // Skip header.
      fgetcsv($handle);

      // Collect UUIDs by entity type for statuses of interest.
      $uuids_by_type = [];

      while (($row = fgetcsv($handle)) !== FALSE) {
        // Expected columns: title, uuid, type, bundle, status.
        $uuid = $row[1] ?? '';
        $type = $row[2] ?? '';
        $status = $row[4] ?? '';

        if ($uuid === '' || $type === '') {
          continue;
        }

        // Only export added/modified to optimize sync.
        if (in_array($status, ['added', 'modified'], TRUE)) {
          $uuids_by_type[$type][] = $uuid;
        }
      }
      fclose($handle);

      if (empty($uuids_by_type)) {
        return $content_sync;
      }

      $content_sync['has_changes'] = TRUE;
      $content_sync['by_type'] = $uuids_by_type;

      // Build export commands per entity type.
      foreach ($uuids_by_type as $entity_type => $uuids) {
        $entities_arg = implode(',', array_unique($uuids));
        $content_sync['export_commands'][] = sprintf(
          'drush content:export %s %s --entities="%s" --assets',
          $entity_type,
          $content_sync['export_dir'],
          $entities_arg
        );
      }

    }
    catch (\Throwable $e) {
      // Be resilient: just return empty commands if something goes wrong.
      $this->logger->warning('Content sync commands generation failed: @msg', ['@msg' => $e->getMessage()]);
    }

    return $content_sync;
  }

  /**
   * Generate a human-readable to-do list text.
   */
  public function generateReadableText(array $todo_list): string {
    $output = [];

    $output[] = "=== VACTORY DIFF - TODO LIST ===";
    $output[] = "";
    $output[] = "Generated: " . ($todo_list['timestamp'] ?? date('c'));
    $output[] = "";
    $output[] = "Summary:";
    $output[] = "- Features to revert: " . $todo_list['summary']['total_features'];
    $output[] = "- Configurations processed: " . $todo_list['summary']['total_configs'];
    $output[] = "- Matched configurations: " . $todo_list['summary']['matched_configs'];
    $output[] = "- Unmatched configurations: " . $todo_list['summary']['unmatched_configs'];
    $output[] = "";

    // Add module installation/uninstallation section.
    $module_changes = $this->moduleInstallation->analyzeModuleChanges();
    if ($module_changes['has_changes']) {
      $output[] = "=== MODULE INSTALLATION/UNINSTALLATION ===";
      $output[] = "";

      if (!empty($module_changes['to_install'])) {
        $output[] = "Modules to Install (" . count($module_changes['to_install']) . "):";
        foreach ($module_changes['to_install'] as $module) {
          $output[] = "  - $module";
        }
        $output[] = "";
      }

      if (!empty($module_changes['to_uninstall'])) {
        $output[] = "Modules to Uninstall (" . count($module_changes['to_uninstall']) . "):";
        foreach ($module_changes['to_uninstall'] as $module) {
          $output[] = "  - $module";
        }
        $output[] = "";
      }

      $output[] = "Commands:";
      foreach ($module_changes['commands'] as $cmd) {
        $output[] = "  " . $cmd['command'];
      }
      $output[] = "";
    }

    $output[] = "=== FEATURE REVERT COMMANDS ===";
    $output[] = "";

    $index = 1;
    foreach ($todo_list['features'] as $feature) {
      $output[] = "$index. Feature: {$feature['module']}";
      $output[] = "   Command: {$feature['command']}";
      $output[] = "   Changes: {$feature['stats']['added']} added, {$feature['stats']['modified']} modified";
      $output[] = "   Configurations:";

      foreach ($feature['configs'] as $config) {
        $output[] = "   - [{$config['change_type']}] {$config['name']} ({$config['type']})";
      }

      $output[] = "";
      $index++;
    }

    if (!empty($todo_list['unmatched_configs'])) {
      $output[] = "=== UNMATCHED CONFIGURATIONS ===";
      $output[] = "";
      $output[] = "The following configurations could not be matched to a feature:";
      $output[] = "";

      foreach ($todo_list['unmatched_configs'] as $unmatched) {
        $output[] = "- {$unmatched['config_name']} ({$unmatched['type']})";
        $output[] = "  Change type: {$unmatched['change_type']}";
        $output[] = "  Reason: {$unmatched['reason']}";
        $output[] = "";
      }
    }

    // Add content sync section (single_content_sync).
    if (!empty($todo_list['content_sync']) && ($todo_list['content_sync']['has_changes'] ?? FALSE)) {
      $output[] = "=== CONTENT SYNC (single_content_sync) ===";
      $output[] = "";
      $output[] = "Export:";
      foreach ($todo_list['content_sync']['export_commands'] as $cmd) {
        $output[] = "  " . $cmd;
      }
      $output[] = "";
      $output[] = "Then copy the generated archive to PROD, then run the import commands";
      $output[] = "drush content:import [archive_path]";
      $output[] = "";
    }

    return implode("\n", $output);
  }

}
