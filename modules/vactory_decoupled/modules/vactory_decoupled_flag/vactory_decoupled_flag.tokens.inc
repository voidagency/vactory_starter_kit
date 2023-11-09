<?php

/**
 * @file
 * Contains related hooks.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function vactory_decoupled_flag_token_info() {

  $flagged_nodes['flagged_nodes'] = [
    'name' => t("Current user flagged nodes"),
    'description' => t("Get current user flagged nodes, replace the '?' with the desired content type machine name (use 'all' to get all content types)"),
    'dynamic' => TRUE,
  ];
  return [
    'tokens' => [
      'vactory' => $flagged_nodes,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_decoupled_flag_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'vactory') {
    foreach ($tokens as $name => $original) {
      if (str_starts_with($name, 'flagged_nodes:')) {
        $splitted = explode(':', $name);
        $bundle = end($splitted);
        $replacement = '';
        $flagged_nodes = \Drupal::service('vactory_decoupled_flag.hepler')->getFlaggedNodes($bundle);
        $flagged_nodes = !empty($flagged_nodes) ? $flagged_nodes : [-1];
        if (!empty($flagged_nodes)) {
          $filter_string = [];
          $filter_string[] = "filter[flag][condition][path]=drupal_internal__nid";
          $filter_string[] = "filter[flag][condition][operator]=IN";
          foreach ($flagged_nodes as $key => $id) {
            $filter_string[] = "filter[flag][condition][value][{$key}]={$id}";
          }
          $replacement = implode("\n", $filter_string);
        }
        $bubbleable_metadata->addCacheTags(['flagging_list']);
        $replacements[$original] = $replacement;
      }
      else {
        $replacements[$original] = '';
      }
    }
  }
  return $replacements;
}
