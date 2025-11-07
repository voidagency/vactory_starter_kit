<?php

namespace Drupal\Tests\vactory_core\Functional;

use Drupal\Core\Entity\EntityInterface;
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
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->languageManager = $this->container->get('language_manager');
    $this->installRequiredModule();
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
   * Ensure a module is installed, track if we installed it.
   */
  private function installRequiredModule(): void {
    $moduleHandler = $this->container->get('module_handler');
    foreach ($this->modulesToInstall as $module) {
      // Check if the module is already installes, otherwise we install it.
      if (!$moduleHandler->moduleExists($module)) {
        $this->modulesToCleanup[] = $module;
      }
    }
    // Install modules which are not already installed.
    $moduleInstaller = $this->container->get('module_installer');
    $moduleInstaller->install($this->modulesToCleanup);
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

    // TearDown should be placed at the end because it destroys the kernel.
    parent::tearDown();
  }

}
