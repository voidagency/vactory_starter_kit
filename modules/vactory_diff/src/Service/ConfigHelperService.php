<?php

namespace Drupal\vactory_diff\Service;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service helper pour la gestion des configurations.
 */
class ConfigHelperService {

  use StringTranslationTrait;

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config entity definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $definitions;

  /**
   * Constructs a ConfigHelperService object.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The configuration storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(StorageInterface $config_storage, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configStorage = $config_storage;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('vactory_diff');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get all configuration data.
   *
   * @return array
   *   Array containing all configuration data.
   */
  public function getAllConfigurations(): array {
    $configs = [];
    try {
      $config_names = $this->configStorage->listAll();

      foreach ($config_names as $config_name) {
        $config_data = $this->configStorage->read($config_name);
        if ($config_data !== FALSE) {
          $configs[$config_name] = $config_data;
        }
      }

      $this->logger->info('Successfully retrieved @count configurations.', [
        '@count' => count($configs),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error retrieving configurations: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $configs;
  }

  /**
   * Get site information for export metadata.
   *
   * @return array
   *   Array containing site information.
   */
  public function getSiteInfo(): array {
    $site_config = $this->configFactory->get('system.site');

    return [
      'site_name' => $site_config->get('name') ?: 'Unknown Site',
      'site_uuid' => $site_config->get('uuid') ?: '',
      'timestamp' => date('c'),
    ];
  }

  /**
   * Validate configuration data structure.
   *
   * @param array $config_data
   *   The configuration data to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateConfigData(array $config_data): bool {
    // Vérifier la structure de base.
    if (!isset($config_data['timestamp']) || !isset($config_data['configs'])) {
      return FALSE;
    }

    // Vérifier que configs est un tableau.
    if (!is_array($config_data['configs'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Detect the configuration entity type from config name.
   *
   * @param string $config_name
   *   The configuration name.
   *
   * @return string
   *   The human-readable configuration type.
   */
  public function detectConfigType(string $config_name): string {
    // Initialize definitions if not already done.
    if (!$this->definitions) {
      $this->definitions = [];
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $definition) {
        if ($definition->entityClassImplements(ConfigEntityInterface::class)) {
          $this->definitions[$entity_type] = $definition;
        }
      }
    }

    // Check if this is a config entity.
    foreach ($this->definitions as $entity_type => $definition) {
      $prefix = $definition->getConfigPrefix() . '.';
      if (str_starts_with($config_name, $prefix)) {
        return $definition->getLabel()->render();
      }
    }

    // If not a config entity, it's a simple configuration.
    return $this->t('Simple configuration')->render();
  }

  /**
   * Group configurations by type.
   *
   * @param array $configs
   *   Array of configurations.
   *
   * @return array
   *   Configurations grouped by type.
   */
  public function groupConfigsByType(array $configs): array {
    $grouped = [];

    foreach ($configs as $config_name => $config_data) {
      $type = $this->detectConfigType($config_name);

      if (!isset($grouped[$type])) {
        $grouped[$type] = [
          'type' => $type,
          'configs' => [],
          'count' => 0,
        ];
      }

      $grouped[$type]['configs'][$config_name] = $config_data;
      $grouped[$type]['count']++;
    }

    // Trier par nom de type.
    ksort($grouped);

    return $grouped;
  }

}
