<?php

/**
 * @file
 * Describes hooks and plugins provided by the Views module.
 */

/**
 * A custom hook to alter the node access.
 *
 * @param boolean $is_accessible
 *   A boolean value to alter, it refers to the node access.
 * @param string $key
 *   Custom action key entered on the node edit form.
 * @param \Drupal\node\NodeInterface $node
 *   Concerned node object.
 */
function hook_vactory_content_access_alter(&$is_accessible, $key, \Drupal\node\NodeInterface $node) {
  if ($key === 'custom_access_for_news') {
    if ($node->bundle() === 'vactory_news' && !$node->isPublished()) {
      $is_accessible = FALSE;
    }
  }
}
