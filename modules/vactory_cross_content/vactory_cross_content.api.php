<?php

/**
 * @file
 * Module API docs.
 */

/**
 * A custom hook to alter the node access.
 *
 * @param array $normalized_node
 *   An array that contains normalized node fields to be altered.
 * @param string $context
 *   Extra data has two keys:
 *     node: the related node object.
 *     node_type: the related node content type machine name.
 * @param string $base_node_type
 *   Base node content type machine name.
 */
function hook_jsonapi_vcc_normalized_node_alter(&$normalized_node, $context, $base_node_type) {
  if ($base_node_type === 'vactory_academy') {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $context['node'];
    $node_type = $context['node_type'];
    if ($node_type === 'vactory_academy') {
      $normalized_node = [
        'title' => $node->label(),
        'body' => $node->get('body')->getValue(),
      ];
    }
  }
}
