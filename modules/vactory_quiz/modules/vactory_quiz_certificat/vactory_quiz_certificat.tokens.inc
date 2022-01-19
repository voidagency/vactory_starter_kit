<?php

/**
 * @file
 * Custom tokens declaration.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;

/**
 * Implements hook_token_info().
 */
function vactory_quiz_certificat_token_info() {
  $type = [
    'name' => t('Quiz'),
    'description' => t('Tokens related to Quiz system'),
  ];

  $tokens = [
    'quiz_title' => [
      'name' => t('Quiz node title'),
      'description' => t('The quiz node title'),
    ],
  ];

  return [
    'types' => ['quiz' => $type],
    'tokens' => ['quiz' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_quiz_certificat_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type === 'quiz') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'quiz_title':
          $replacements[$original] = isset($data['quiz_title']) && !empty($data['quiz_title']) ? $data['quiz_title'] : '';
          break;
      }
    }
  }
  return $replacements;
}