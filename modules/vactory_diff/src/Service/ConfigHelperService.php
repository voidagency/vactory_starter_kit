<?php

namespace Drupal\vactory_diff\Service;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service helper pour la gestion des configurations.
 */
class ConfigHelperService {

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
   * Constructs a ConfigHelperService object.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The configuration storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(StorageInterface $config_storage, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->configStorage = $config_storage;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('vactory_diff');
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
   * Get configuration data for a specific config name.
   *
   * @param string $config_name
   *   The configuration name.
   *
   * @return array|null
   *   The configuration data or NULL if not found.
   */
  public function getConfiguration(string $config_name): ?array {
    try {
      $config_data = $this->configStorage->read($config_name);
      return $config_data !== FALSE ? $config_data : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error retrieving configuration @name: @message', [
        '@name' => $config_name,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
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

}
