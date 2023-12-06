<?php

namespace Drupal\vactory_jsonapi_cross_bundles;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Overrides jsonapi includeResolver.
 */
class VactoryIncludeResolver extends IncludeResolver {

  /**
   * Original service.
   *
   * @var \Drupal\jsonapi\IncludeResolver
   */
  protected IncludeResolver $includeResolver;

  /**
   * IncludeResolver constructor.
   */
  public function __construct(IncludeResolver $includeResolver, EntityTypeManagerInterface $entity_type_manager, EntityAccessChecker $entity_access_checker) {
    $this->includeResolver = $includeResolver;
    parent::__construct($entity_type_manager, $entity_access_checker);
  }

  /**
   * Override method (use service instead of calling class).
   */
  protected static function resolveInternalIncludePaths(ResourceType $base_resource_type, array $paths) {
    $internal_paths = array_map(function ($exploded_path) use ($base_resource_type) {
      if (empty($exploded_path)) {
        return [];
      }
      return \Drupal::service('jsonapi.field_resolver')->resolveInternalIncludePath($base_resource_type, $exploded_path);
    }, $paths);
    $flattened_paths = array_reduce($internal_paths, 'array_merge', []);
    return $flattened_paths;
  }

}
