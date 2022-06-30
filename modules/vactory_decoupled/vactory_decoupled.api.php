<?php

/**
 * @file
 * Hooks specific to the Vactory decoupled module.
 */

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
