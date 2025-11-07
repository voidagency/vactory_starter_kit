<?php

namespace Drupal\Tests\vactory_core\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Serialization\Yaml;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for Vactory functional tests.
 */
abstract class VactoryExistingSiteBase extends ExistingSiteBase {

  /**
   * List of modules to be installed for the test.
   *
   * @var string[]
   */
  protected array $modulesToInstall = [];

  /**
   * List of modules to be uninstalled after the test.
   *
   * @var string[]
   */
  protected array $modulesToCleanup = [];

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Configs to be restored.
   *
   * @var array
   */
  private $cleanUpConfigs = [];

  /**
   * Track created DF files for cleanup.
   *
   * @var array
   */
  protected $createdDfFiles = [];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->languageManager = $this->container->get('language_manager');
    $this->prepareRequiredModules();
  }

  /**
   * Fetch node jsonapi.
   */
  protected function fetchNodeJsonApi(EntityInterface $entity, $langcode, $expectedStatusCode = 200, $queryParams = []) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_uuid = $entity->uuid();

    $path = $this->buildJsonApiItemPath($entity_type, $bundle, $entity_uuid);

    // Full URL (for request context).
    $fullUrl = "{$this->baseUrl}/{$langcode}{$path}";
    if (!empty($queryParams)) {
      $fullUrl .= '?' . http_build_query($queryParams);
    }

    // Fetch the node via JSON:API.
    $this->drupalGet($fullUrl);
    // Assert response status.
    $this->assertSession()->statusCodeEquals($expectedStatusCode);

    // Decode response.
    return json_decode($this->getSession()->getPage()->getContent(), TRUE);
  }

  /**
   * Builds the full JSON:API path for a specific entity item.
   *
   * @param string $entity_type
   *   The entity type ID (e.g., 'node', 'taxonomy_term').
   * @param string $bundle
   *   The bundle name (e.g., 'vactory_page', 'article').
   * @param string $uuid
   *   The UUID of the entity.
   *
   * @return string
   *   The complete JSON:API path (e.g., '/api/node/vactory_page/uuid-here').
   */
  private function buildJsonApiItemPath(string $entity_type, string $bundle, string $uuid): string {
    $prefix = $this->getJsonApiPrefix();
    $relative = $this->getResourceRelativePath($entity_type, $bundle);
    return $prefix . '/' . $relative . '/' . $uuid;
  }

  /**
   * Gets the relative resource path for a given entity type and bundle.
   *
   * Attempts to retrieve the path from the JSON:API resource type repository.
   * Falls back to the default structure (entity_type/bundle) if unavailable.
   *
   * @param string $entity_type
   *   The entity type ID (e.g., 'node', 'taxonomy_term').
   * @param string $bundle
   *   The bundle name (e.g., 'vactory_page', 'article').
   *
   * @return string
   *   The relative resource path (e.g., 'node/vactory_page').
   */
  private function getResourceRelativePath(string $entity_type, string $bundle): string {
    try {
      $resourceTypeRepository = $this->container->get('jsonapi.resource_type.repository');
      $resource_type = $resourceTypeRepository->get($entity_type, $bundle);
      $path = $resource_type->getPath();
      if (is_string($path) && $path !== '') {
        return trim($path, '/');
      }
    }
    catch (\Throwable $e) {
      // Fallback will be used.
    }
    // Fallback to core default structure.
    return trim($entity_type . '/' . $bundle, '/');
  }

  /**
   * Gets the JSON:API prefix path.
   *
   * Retrieves the configured JSON:API prefix from jsonapi_extras settings,
   * or defaults to 'api' if not configured.
   *
   * @return string
   *   The JSON:API prefix with leading slash (e.g., '/api').
   */
  private function getJsonApiPrefix(): string {
    $prefix = $this->configFactory->get('jsonapi_extras.settings')->get('path_prefix');
    if (!is_string($prefix) || $prefix === '') {
      $prefix = 'api';
    }
    return '/' . ltrim($prefix, '/');
  }

  /**
   * Modifies a configuration value and stores the original for cleanup.
   *
   * This method updates a configuration value and tracks the original value
   * so it can be restored during tearDown().
   *
   * @param string $config_name
   *   The configuration object name (e.g., 'system.site').
   * @param string $key
   *   The configuration key to modify (e.g., 'page.front').
   * @param mixed $value
   *   The new value to set.
   * @param string|null $langcode
   *   (optional) The language code. If NULL, uses default language.
   */
  protected function modifyConfigValue($config_name, $key, $value, $langcode = NULL) {
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();

    // Normaliser le langcode.
    if (!$langcode) {
      $langcode = $default_langcode;
    }

    if ($langcode !== $default_langcode) {
      // Cas de traductions.
      $config = $this->languageManager->getLanguageConfigOverride($langcode, $config_name);
      $storage_key = $langcode;
    }
    else {
      // Cas de langue par défaut.
      $config = $this->configFactory->getEditable($config_name);
      $storage_key = 'default';
    }
    // Keep original value.
    $original = $config->get($key);
    $this->cleanUpConfigs[$storage_key][$config_name][$key] = $original;

    // Set new value.
    $config->set($key, $value);
    $config->save();
  }

  /**
   * Check if the required module is installed,otherwise we install it.
   */
  private function prepareRequiredModules(): void {
    $moduleHandler = $this->container->get('module_handler');
    $missing_modules = [];
    foreach ($this->modulesToInstall as $module) {
      // Check if the module is already installes, otherwise we install it.
      if (!$moduleHandler->moduleExists($module)) {
        $missing_modules[] = $module;
      }
    }
    // Install modules which are not already installed.
    if (!empty($missing_modules)) {
      $this->installModules($missing_modules);
    }
  }

  /**
   * Installs the given modules and tracks them for cleanup.
   */
  private function installModules(array $modules) {
    $this->modulesToCleanup = [...$this->modulesToCleanup, ...$modules];
    $moduleInstaller = $this->container->get('module_installer');
    $moduleInstaller->install($modules);
  }

  /**
   * Creates a volatile dynamic field (DF) configuration file.
   *
   * Installs the required module 'vactory_dynamic_field_volatile',
   * encodes the settings as YAML, writes them to a private directory,
   * and tracks the directory for cleanup.
   *
   * @param array $settings
   *   The dynamic field settings to save.
   * @param string $name
   *   The name of the dynamic field (used for directory and file naming).
   *
   * @return string
   *   A string combining the module name and the DF name, in the format
   *   "module_name:df_name".
   */
  protected function createVolatileDf(array $settings, string $name) {
    $df_creator_module = 'vactory_dynamic_field_volatile';
    $this->installModules([$df_creator_module]);
    $yaml_config = Yaml::encode($settings);
    $dest_uri = 'private://volatile-df';
    $dest_df_uri = $dest_uri . '/' . $name;

    if (!file_exists($dest_df_uri)) {
      mkdir($dest_df_uri, 0777, TRUE);
    }

    $filepath = \Drupal::service('file_system')->realpath($dest_df_uri . '/settings.yml');
    file_put_contents($filepath, $yaml_config);

    // Track the directory for cleanup.
    $this->createdDfFiles[] = \Drupal::service('file_system')->realpath($dest_df_uri);

    return implode(':', [$df_creator_module, $name]);
  }

  /**
   * Recursively removes a directory and all its contents.
   *
   * @param string $dir
   *   The path to the directory to remove.
   */
  private function removeDirectory(string $dir): void {
    if (!is_dir($dir)) {
      return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      $path = $dir . DIRECTORY_SEPARATOR . $file;
      if (is_dir($path)) {
        $this->removeDirectory($path);
      }
      else {
        unlink($path);
      }
    }
    rmdir($dir);
  }

  /**
   * {@inheritdoc}
   *
   * Restores all modified configuration values to their original state.
   */
  protected function tearDown(): void {
    // Cleanup configs.
    foreach ($this->cleanUpConfigs as $storage_key => $configs) {
      foreach ($configs as $config_name => $config_values) {

        // Distinguer langue par défaut vs traductions.
        if ($storage_key === 'default') {
          $config = $this->configFactory->getEditable($config_name);
        }
        else {
          $config = $this->languageManager->getLanguageConfigOverride($storage_key, $config_name);
        }

        foreach ($config_values as $key => $value) {
          $config->set($key, $value);
        }
        $config->save();
      }
    }
    $this->cleanUpConfigs = [];

    // Cleanup modules.
    // Uninstall modules installed during the test.
    if (!empty($this->modulesToCleanup)) {
      $moduleInstaller = $this->container->get('module_installer');
      $moduleInstaller->uninstall($this->modulesToCleanup);
    }

    // Cleanup created DF files.
    foreach ($this->createdDfFiles as $dfPath) {
      if (file_exists($dfPath)) {
        $this->removeDirectory($dfPath);
      }
    }

    // TearDown should be placed at the end because it destroys the kernel.
    parent::tearDown();
  }

}
