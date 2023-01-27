<?php

/**
 * @file
 * Hooks specific to the Vactory decoupled module.
 */

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Alter the internal block classification.
 *
 * @param string $classification
 *   The block classification to be altered.
 * @param array $block_info
 *   The block info.
 */
function hook_internal_block_classification_alter(string &$classification, array $block_info) {
  if (isset($block_info['content']) && is_array($block_info['content']) && isset($block_info['content']['widget_id'])) {
    list($provider, $tpl) = explode(':', $block_info['content']['widget_id']);
    if (strpos($provider, 'video_help') !== FALSE) {
      // Change block classification to video_help.
      $classification = 'video_help';
    }
  }
}

/**
 * Alter DF components format.
 *
 * @param mixed $value
 *   The component value.
 * @param array $info
 *   DF settings infos.
 * @param CacheableMetadata $cacheability
 *   DF settings infos.
 */
function hook_decoupled_df_format_alter(&$value, $info, CacheableMetadata &$cacheability) {
  if ($info['type'] === 'webform_decoupled' && !empty($value)) {
    $webform_id = $value['id'];
    $value['elements'] = \Drupal::service('vactory.webform.normalizer')->normalize($webform_id);
    $cacheability->setCacheTags(['webform_list']);
  }
}

/**
 * Alter entity reference select options.
 *
 * @param array $entities
 *   Array of entities.
 * @param array $info
 *   DF info.
 * @param CacheableMetadata $cacheability
 *   Related cacheability object.
 */
function hook_decoupled_entity_reference_options_alter(array &$entities, array &$info, CacheableMetadata $cacheability) {
  if (isset($info['uuid']) && $info['uuid'] = 'vactory_news:listing') {
    // Filter options (remove term with label Action).
    $entities = array_filter($entities, function ($entity) {
      return $entity->label() !== 'Action';
    });
  }
}

/**
 * Alter internal blocks cacheability.
 *
 * @param CacheableMetadata $cacheability
 *   Related cacheability object.
 * @param $entity
 *   Related node entity.
 * @param $value
 *   Array of internal blocks infos.
 */
function hook_internal_blocks_cacheability_alter(CacheableMetadata $cacheability, $entity, $value) {
  if ($entity instanceof \Drupal\node\NodeInterface && $entity->bundle() === 'vactory_page') {
    $cacheability->addCacheTags(['locator_entity_list']);
  }
}
