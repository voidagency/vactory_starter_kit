<?php

namespace Drupal\vactory_diff_content\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Service jsonapi ressources.
 */
class JsonApiPathHelper {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Resource Type Repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * Constructs the service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ResourceTypeRepositoryInterface $resource_type_repository) {
    $this->configFactory = $config_factory;
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * Returns the JSON:API prefix (e.g. '/api' or '/jsonapi').
   */
  public function getPrefix(): string {
    $prefix = $this->configFactory->get('jsonapi_extras.settings')->get('path_prefix');
    if (!is_string($prefix) || $prefix === '') {
      $prefix = '/api';
    }
    return '/' . ltrim($prefix, '/');
  }

  /**
   * Get the resource relative path (e.g. 'node/vactory_page') from config.
   */
  public function getResourceRelativePath(string $entity_type, string $bundle): string {
    try {
      $resource_type = $this->resourceTypeRepository->get($entity_type, $bundle);
      $path = $resource_type->getPath();
      if (is_string($path) && $path !== '') {
        return trim($path, '/');
      }
    }
    catch (\Throwable $e) {
      // Fallback will be used.
    }
    return trim($entity_type . '/' . $bundle, '/');
  }

  /**
   * Build the full item path: prefix + resource path + uuid.
   */
  public function buildItemPath(string $entity_type, string $bundle, string $uuid): string {
    return $this->getPrefix() . '/' . $this->getResourceRelativePath($entity_type, $bundle) . '/' . $uuid;
  }

}
