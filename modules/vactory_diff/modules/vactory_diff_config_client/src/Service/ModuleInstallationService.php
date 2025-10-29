<?php

namespace Drupal\vactory_diff_config_client\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for analyzing module installation/uninstallation from core.extension.
 */
class ModuleInstallationService {

  /**
   * Modules to ignore in the TODO list.
   */
  const IGNORED_MODULES = [
    'vactory_diff',
    'vactory_diff_config_client',
    'vactory_diff_config_server',
    'vactory_diff_content',
  ];

  /**
   * The config comparison service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\ConfigComparisonService
   */
  protected $comparisonService;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a ModuleInstallationService object.
   *
   * @param \Drupal\vactory_diff_config_client\Service\ConfigComparisonService $comparison_service
   *   The config comparison service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigComparisonService $comparison_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->comparisonService = $comparison_service;
    $this->logger = $logger_factory->get('vactory_diff_config_client');
  }

  /**
   * Analyze module installation/uninstallation from comparison results.
   *
   * @param array|null $comparison_results
   *   The comparison results. If NULL, loads from saved results.
   *
   * @return array
   *   Array with 'to_install' and 'to_uninstall' module lists.
   */
  public function analyzeModuleChanges(?array $comparison_results = NULL): array {
    if ($comparison_results === NULL) {
      $comparison_results = $this->comparisonService->loadComparisonResults();
    }

    if ($comparison_results === NULL) {
      return [
        'to_install' => [],
        'to_uninstall' => [],
        'commands' => [],
      ];
    }

    $modules_to_install = [];
    $modules_to_uninstall = [];

    // Merge built-in ignored modules with user-configured ignored modules.
    $ignored_modules = self::IGNORED_MODULES;
    try {
      $config = \Drupal::config('vactory_diff_config_client.settings');
      $ignored_text = (string) ($config->get('ignored_modules') ?? '');
      if ($ignored_text !== '') {
        $extra_ignored = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $ignored_text)));
        if (!empty($extra_ignored)) {
          $ignored_modules = array_unique(array_merge($ignored_modules, $extra_ignored));
        }
      }
      // Normalize to lowercase for comparisons.
      $ignored_modules = array_map('strtolower', $ignored_modules);
    }
    catch (\Throwable $e) {
      // Fallback to built-in list on any error.
      $ignored_modules = array_map('strtolower', self::IGNORED_MODULES);
    }

    // Check for core.extension in modified configs.
    $modified_configs = $comparison_results['differences_by_type']['modified'] ?? [];
    $core_extension = NULL;

    foreach ($modified_configs as $type => $configs) {
      foreach ($configs as $config) {
        if ($config['name'] === 'core.extension') {
          $core_extension = $config;
          break 2;
        }
      }
    }

    if ($core_extension === NULL) {
      $this->logger->debug('No core.extension changes found in comparison results.');
      return [
        'to_install' => [],
        'to_uninstall' => [],
        'commands' => [],
      ];
    }

    // Analyze the differences in core.extension.
    $local_modules = $core_extension['local_data']['module'] ?? [];
    $remote_modules = $core_extension['remote_data']['module'] ?? [];

    $this->logger->debug('Analyzing core.extension: @local_count local modules, @remote_count remote modules', [
      '@local_count' => count($local_modules),
      '@remote_count' => count($remote_modules),
    ]);

    // Find modules that are in local but not in remote (added).
    foreach ($local_modules as $module_name => $weight) {
      if (!isset($remote_modules[$module_name])) {
        if (!in_array(strtolower($module_name), $ignored_modules, TRUE)) {
          $modules_to_install[] = $module_name;
        }
      }
    }

    // Find modules that are in remote but not in local (removed).
    foreach ($remote_modules as $module_name => $weight) {
      if (!isset($local_modules[$module_name])) {
        if (!in_array(strtolower($module_name), $ignored_modules, TRUE)) {
          $modules_to_uninstall[] = $module_name;
        }
      }
    }

    // Check for conflicts: a module that appears in both install and uninstall
    // lists should be removed from both (it's a diff error).
    $conflicts = array_intersect($modules_to_install, $modules_to_uninstall);
    if (!empty($conflicts)) {
      $this->logger->warning('Found conflicting module changes (both added and removed): @modules. These will be ignored.', [
        '@modules' => implode(', ', $conflicts),
      ]);

      $modules_to_install = array_diff($modules_to_install, $conflicts);
      $modules_to_uninstall = array_diff($modules_to_uninstall, $conflicts);
    }

    // Generate commands.
    $commands = $this->generateCommands($modules_to_install, $modules_to_uninstall);

    $this->logger->info('Module changes analysis: @install to install, @uninstall to uninstall', [
      '@install' => count($modules_to_install),
      '@uninstall' => count($modules_to_uninstall),
    ]);

    return [
      'to_install' => array_values($modules_to_install),
      'to_uninstall' => array_values($modules_to_uninstall),
      'commands' => $commands,
      'has_changes' => !empty($modules_to_install) || !empty($modules_to_uninstall),
    ];
  }

  /**
   * Generate drush commands for module installation/uninstallation.
   *
   * @param array $to_install
   *   Modules to install.
   * @param array $to_uninstall
   *   Modules to uninstall.
   *
   * @return array
   *   Array of command strings.
   */
  protected function generateCommands(array $to_install, array $to_uninstall): array {
    $commands = [];

    // Install commands.
    foreach ($to_install as $module) {
      $commands[] = [
        'command' => "drush en $module -y",
        'type' => 'install',
        'module' => $module,
      ];
    }

    // Uninstall commands.
    foreach ($to_uninstall as $module) {
      $commands[] = [
        'command' => "drush pmu $module -y",
        'type' => 'uninstall',
        'module' => $module,
      ];
    }

    return $commands;
  }

}
