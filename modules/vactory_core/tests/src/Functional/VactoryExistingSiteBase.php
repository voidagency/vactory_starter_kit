<?php

namespace Drupal\Tests\vactory_core\Functional;

use Drupal\Core\Entity\EntityInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for Vactory functional tests.
 */
abstract class VactoryExistingSiteBase extends ExistingSiteBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Fetch node jsonapi.
   */
  protected function fetchNodeJsonApi(EntityInterface $entity, $langcode, $expectedStatusCode = 200) {

    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_uuid = $entity->uuid();

    $path = $this->buildJsonApiItemPath($entity_type, $bundle, $entity_uuid);

    // Full URL (for request context).
    $fullUrl = "{$this->baseUrl}/{$langcode}{$path}";

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

}
