<?php

namespace Drupal\Tests\vactory_core\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Vocabulary;
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
   * Track created webforms for cleanup.
   *
   * @var array
   */
  protected $cleanupWebforms = [];

  /**
   * Track created vocabularies for cleanup.
   *
   * @var array
   */
  protected $cleanupVocabularies = [];

  /**
   * Track created file entities for cleanup.
   *
   * @var array
   */
  protected $cleanupFileEntities = [];

  /**
   * Track created media entities for cleanup.
   *
   * @var array
   */
  protected $cleanupMediaEntities = [];

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
  protected function removeDirectory(string $dir): void {
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
   * Creates and saves a new paragraph entity from the given values.
   *
   * This method creates a "paragraph" entity using the provided values,
   * saves it to the database, and adds it to the cleanup list to be
   * deleted later.
   *
   * @param array $values
   *   An associative array of field values for the paragraph.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   The created and saved paragraph entity.
   */
  protected function createParagraph(array $values) {
    $paragraphStorage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $paragraph = $paragraphStorage->create($values);
    $paragraph->save();
    $this->cleanupEntities[] = $paragraph;
    return $paragraph;
  }

  /**
   * Creates a webform entity.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return \Drupal\webform\Entity\Webform
   *   The created webform entity.
   */
  protected function createWebform(array $values) {
    $storage = \Drupal::entityTypeManager()->getStorage('webform');
    $webform = $storage->create($values);
    $webform->save();
    $this->cleanupWebforms[] = $webform;
    return $webform;
  }

  /**
   * Creates a vocabulary entity.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary
   *   The created vocabulary entity.
   */
  protected function createVocabularyType(array $values): Vocabulary {
    $vocabulary = Vocabulary::create($values);
    $vocabulary->save();
    $this->cleanupVocabularies[] = $vocabulary;
    return $vocabulary;
  }

  /**
   * Creates a file entity.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   * @param int $width
   *   Width of the generated test image in pixels.
   * @param int $height
   *   Height of the generated test image in pixels.
   *
   * @return \Drupal\file\Entity\File
   *   The created file entity.
   */
  protected function createFile(array $values, $width = 10, $height = 10): File {
    if ($uri = $values['uri']) {
      $img = imagecreatetruecolor($width, $height);
      $bg = imagecolorallocate($img, 255, 0, 0);
      imagefill($img, 0, 0, $bg);
      imagejpeg($img, \Drupal::service('file_system')->realpath($uri));
      imagedestroy($img);
    }

    $file = File::create($values);
    $file->save();
    $this->cleanupFileEntities[] = $file;
    return $file;
  }

  /**
   * Creates a media entity.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return \Drupal\media\Entity\Media
   *   The created media entity.
   */
  protected function createMedia(array $values) {
    $media = Media::create($values);
    $media->save();
    $this->cleanupMediaEntities[] = $media;
    return $media;
  }

  /**
   * {@inheritdoc}
   *
   * Restores all modified configuration values to their original state.
   */
  protected function tearDown(): void {
    $this->restoreConfigs();
    $this->uninstallCleanupModules();
    $this->cleanupDfFiles();

    $this->cleanupEntitiesByType($this->cleanupWebforms);
    $this->cleanupEntitiesByType($this->cleanupVocabularies);
    $this->cleanupEntitiesByType($this->cleanupFileEntities);
    $this->cleanupEntitiesByType($this->cleanupMediaEntities);

    parent::tearDown();
  }

  /**
   * Restores configuration values changed during the test.
   */
  private function restoreConfigs(): void {
    foreach ($this->cleanUpConfigs as $storage_key => $configs) {
      foreach ($configs as $config_name => $config_values) {
        $config = $storage_key === 'default'
          ? $this->configFactory->getEditable($config_name)
          : $this->languageManager->getLanguageConfigOverride($storage_key, $config_name);

        foreach ($config_values as $key => $value) {
          $config->set($key, $value);
        }
        $config->save();
      }
    }
    $this->cleanUpConfigs = [];
  }

  /**
   * Uninstalls modules that were installed the test execution.
   */
  private function uninstallCleanupModules(): void {
    if (!empty($this->modulesToCleanup)) {
      $moduleInstaller = $this->container->get('module_installer');
      $moduleInstaller->uninstall($this->modulesToCleanup);
    }
    $this->modulesToCleanup = [];
  }

  /**
   * Removes temporary DF directories.
   */
  private function cleanupDfFiles(): void {
    foreach ($this->createdDfFiles as $dfPath) {
      if (file_exists($dfPath)) {
        $this->removeDirectory($dfPath);
      }
    }
    $this->createdDfFiles = [];
  }

  /**
   * Deletes entities from a list.
   */
  private function cleanupEntitiesByType(array &$entities): void {
    foreach ($entities as $e) {
      $e->delete();
    }
    $entities = [];
  }

}
