<?php

namespace Drupal\vactory_jsonapi_cross_bundles;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\Core\Session\AccountInterface;

/**
 * Overrides jsonapi fieldResolver.
 */
class VactoryFieldResolver extends FieldResolver {

  /**
   * Original service.
   *
   * @var \Drupal\jsonapi\Context\FieldResolver
   */
  protected FieldResolver $fieldResolver;

  /**
   * Creates a VactoryFieldResolver instance.
   */
  public function __construct(FieldResolver $fieldResolver, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, ResourceTypeRepositoryInterface $resource_type_repository, ModuleHandlerInterface $module_handler, AccountInterface $current_user = NULL) {
    $this->fieldResolver = $fieldResolver;
    parent::__construct(
      $entity_type_manager,
      $field_manager,
      $entity_type_bundle_info,
      $resource_type_repository,
      $module_handler,
      $current_user
    );
  }

  /**
   * Override method (disable includes validation).
   */
  public static function resolveInternalIncludePath(ResourceType $resource_type, array $path_parts, $depth = 0) {
    $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:include']);
    if (empty($path_parts[0])) {
      throw new CacheableBadRequestHttpException($cacheability, 'Empty include path.');
    }
    $public_field_name = $path_parts[0];
    $internal_field_name = $resource_type->getInternalName($public_field_name);
    $relatable_resource_types = $resource_type->getRelatableResourceTypesByField($public_field_name);
    $remaining_parts = array_slice($path_parts, 1);
    if (empty($remaining_parts)) {
      return [[$internal_field_name]];
    }
    $exceptions = [];
    $resolved = [];
    foreach ($relatable_resource_types as $relatable_resource_type) {
      try {
        // Each resource type may resolve the path differently and may return
        // multiple possible resolutions.
        $resolved = array_merge($resolved, static::resolveInternalIncludePath($relatable_resource_type, $remaining_parts, $depth + 1));
      }
      catch (CacheableBadRequestHttpException $e) {
        $exceptions[] = $e;
      }
    }
    if (!empty($exceptions) && count($exceptions) === count($relatable_resource_types)) {
      $previous_messages = implode(' ', array_unique(array_map(function (CacheableBadRequestHttpException $e) {
        return $e->getMessage();
      }, $exceptions)));
      // Only add the full include path on the first level of recursion so that
      // the invalid path phrase isn't repeated at every level.
      throw new CacheableBadRequestHttpException($cacheability, $depth === 0
        ? sprintf("`%s` is not a valid include path. $previous_messages", implode('.', $path_parts))
        : $previous_messages
      );
    }
    // Remove duplicates by converting to strings and then using array_unique().
    $resolved_as_strings = array_map(function ($possibility) {
      return implode('.', $possibility);
    }, $resolved);
    $resolved_as_strings = array_unique($resolved_as_strings);

    // The resolved internal paths do not include the current field name because
    // resolution happens in a recursive process. Convert back from strings.
    return array_map(function ($possibility) use ($internal_field_name) {
      return array_merge([$internal_field_name], explode('.', $possibility));
    }, $resolved_as_strings);
  }

}
