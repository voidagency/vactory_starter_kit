<?php

/**
 * @file
 * Builds placeholder replacement tokens for Vactory Help Center module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_token_info().
 */
function vactory_help_center_token_info() {
  $types['vactory_help_center'] = [
    'name' => new TranslatableMarkup('Vactory Help Center'),
    'description' => new TranslatableMarkup('Tokens for Vactory Help Center module.'),
  ];

  $tokens['vactory_help_center']['alias'] = [
    'name' => new TranslatableMarkup('Alias'),
    'description' => new TranslatableMarkup('Generates an alias based on the taxonomy hierarchy and node title.'),
  ];

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_help_center_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'vactory_help_center' && !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'alias':
          $alias_service = \Drupal::service('vactory_help_center.helper');
          $alias = $alias_service->generateAlias($node);
          $replacements[$original] = trim($alias, '/');
          break;
      }
    }
  }

  return $replacements;
}
