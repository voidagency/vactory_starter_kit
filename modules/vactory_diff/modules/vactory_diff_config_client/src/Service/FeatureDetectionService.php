<?php

namespace Drupal\vactory_diff_config_client\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Service for detecting which feature module provides a configuration.
 */
class FeatureDetectionService {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache of module configs.
   *
   * @var array
   */
  protected $moduleConfigsCache = [];

  /**
   * Constructs a FeatureDetectionService object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ModuleExtensionList $module_extension_list,
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory
  ) {
    $this->moduleHandler = $module_handler;
    $this->moduleExtensionList = $module_extension_list;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('vactory_diff_config_client');
    $this->configFactory = $config_factory;
  }

  /**
   * Find which module provides a specific configuration.
   */
  public function findModuleForConfig(string $config_name): ?array {
    // Scan all modules for this config.
    $modules = $this->getModulesWithConfigs();

    foreach ($modules as $module_name => $module_path) {
      $config_path = $module_path . '/config/install/' . $config_name . '.yml';

      if (file_exists($config_path)) {
        try {
          $config_data = Yaml::parseFile($config_path);
          return [
            'module' => $module_name,
            'config_data' => $config_data,
            'config_path' => $config_path,
          ];
        }
        catch (\Exception $e) {
          $this->logger->warning('Error parsing config @config in module @module: @message', [
            '@config' => $config_name,
            '@module' => $module_name,
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }

    return NULL;
  }

  /**
   * Get all custom modules that have config/install directories.
   *
   * Only scans modules in the configured custom modules paths.
   *
   * @return array
   *   Array of module_name => module_path.
   */
  protected function getModulesWithConfigs(): array {
    if (!empty($this->moduleConfigsCache)) {
      return $this->moduleConfigsCache;
    }

    // Get the configured custom modules paths (can be multiple, one per line).
    $config = $this->configFactory->get('vactory_diff_config_client.settings');
    $custom_modules_paths_string = $config->get('custom_modules_path') ?: 'modules/custom';

    // Parse multiple paths (separated by newlines).
    $custom_modules_paths = array_filter(
      array_map('trim', explode("\n", $custom_modules_paths_string)),
      fn($path) => !empty($path)
    );

    if (empty($custom_modules_paths)) {
      $custom_modules_paths = ['modules/custom'];
    }

    $modules_with_configs = [];
    $all_modules = $this->moduleExtensionList->getList();

    $this->logger->info('Scanning custom modules in @count path(s): @paths', [
      '@count' => count($custom_modules_paths),
      '@paths' => implode(', ', $custom_modules_paths),
    ]);

    foreach ($all_modules as $module_name => $module) {
      $module_path = $module->getPath();

      // Check if module is in any of the configured custom modules directories.
      $is_in_custom_path = FALSE;
      foreach ($custom_modules_paths as $custom_path) {
        if (str_contains($module_path, $custom_path)) {
          $is_in_custom_path = TRUE;
          break;
        }
      }

      if (!$is_in_custom_path) {
        continue;
      }

      $config_install_path = DRUPAL_ROOT . '/' . $module_path . '/config/install';

      if (is_dir($config_install_path)) {
        $modules_with_configs[$module_name] = DRUPAL_ROOT . '/' . $module_path;

        $this->logger->debug('Found custom module with configs: @module at @path', [
          '@module' => $module_name,
          '@path' => $module_path,
        ]);
      }
    }

    $this->logger->info('Scanned custom modules in @count path(s): found @modules_count modules with configurations', [
      '@count' => count($custom_modules_paths),
      '@modules_count' => count($modules_with_configs),
    ]);

    $this->moduleConfigsCache = $modules_with_configs;
    return $modules_with_configs;
  }

  /**
   * Check if a config change matches the module's config.
   *
   * For both 'added' and 'modified' configs, we check if the local version
   * matches the module's config. This tells us that the change is part of
   * a feature and can be reverted with `drush fr`.
   *
   * @param array $changed_config
   *   The changed configuration data.
   * @param array $module_config
   *   The module's configuration data.
   *
   * @return bool
   *   TRUE if the change matches the module config.
   */
  public function configMatchesModule(array $changed_config, array $module_config): bool {
    // Both 'added' and 'modified' configs need to match the module's config
    // to be considered part of a revertable feature.
    return $this->arraysMatch($changed_config, $module_config);
  }

  /**
   * Compare two arrays for equality (ignoring UUIDs and some metadata).
   *
   * @param array $array1
   *   First array.
   * @param array $array2
   *   Second array.
   *
   * @return bool
   *   TRUE if arrays match.
   */
  protected function arraysMatch(array $array1, array $array2): bool {
    // Remove fields that can differ but don't affect functionality.
    $ignore_keys = ['uuid', '_core'];

    $array1_filtered = $this->filterArray($array1, $ignore_keys);
    $array2_filtered = $this->filterArray($array2, $ignore_keys);

    return json_encode($array1_filtered) === json_encode($array2_filtered);
  }

  /**
   * Filter an array by removing specified keys.
   *
   * @param array $array
   *   The array to filter.
   * @param array $ignore_keys
   *   Keys to ignore.
   *
   * @return array
   *   Filtered array.
   */
  protected function filterArray(array $array, array $ignore_keys): array {
    foreach ($ignore_keys as $key) {
      unset($array[$key]);
    }

    // Recursively filter nested arrays.
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $array[$key] = $this->filterArray($value, $ignore_keys);
      }
    }

    return $array;
  }

  /**
   * Check if a module is a feature (has features.yml file).
   *
   * @param string $module_name
   *   The module name.
   *
   * @return bool
   *   TRUE if the module is a feature.
   */
  public function isFeature(string $module_name): bool {
    try {
      $module_path = $this->moduleExtensionList->getPath($module_name);
      $features_file = DRUPAL_ROOT . '/' . $module_path . '/' . $module_name . '.features.yml';
      return file_exists($features_file);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get all installed feature modules.
   *
   * @return array
   *   Array of feature module names.
   */
  public function getAllFeatures(): array {
    $features = [];
    $all_modules = $this->moduleExtensionList->getList();

    foreach ($all_modules as $module_name => $module) {
      if ($this->isFeature($module_name)) {
        $features[] = $module_name;
      }
    }

    return $features;
  }

}
