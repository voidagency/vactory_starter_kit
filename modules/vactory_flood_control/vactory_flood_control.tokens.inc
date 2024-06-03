<?php

/**
 * @file
 * Custom tokens declaration.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\UserInterface;

/**
 * Implements hook_token_info().
 */
function vactory_flood_control_token_info() {
  $type = [
    'name' => t('Flood control - Blocked user'),
    'description' => t('Flood control - Blocked user'),
  ];

  $tokens = [
    'uid' => [
      'name' => t('UID'),
      'description' => t('Blocked user ID'),
    ],
    'username' => [
      'name' => t('Username'),
      'description' => t('Blocked user name'),
    ],
    'email' => [
      'name' => t('E-mail'),
      'description' => t('Blocked user mail'),
    ],
    'ip' => [
      'name' => t('IP'),
      'description' => t('Blocked IP'),
    ],
  ];

  return [
    'types' => ['vactory_flood_control' => $type],
    'tokens' => ['vactory_flood_control' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_flood_control_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type === 'vactory_flood_control') {
    $user = $data['vactory_flood_control_user'] ?? NULL;
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'uid':
          $replacements[$original] = $user instanceof UserInterface ? $user->id() : '';
          break;

        case 'username':
          $replacements[$original] = $user instanceof UserInterface ? $user->getAccountName() : '';
          break;

        case 'email':
          $replacements[$original] = $user instanceof UserInterface ? $user->getEmail() : '';
          break;

        case 'ip':
          $replacements[$original] = $data['vactory_flood_control_ip'];
          break;
      }
    }
  }
  return $replacements;
}
