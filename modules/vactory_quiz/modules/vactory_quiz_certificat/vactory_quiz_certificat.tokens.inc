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
    'title' => [
      'name' => t('Quiz node title'),
      'description' => t('The quiz node title'),
    ],
    'certificate_url' => [
      'name' => t('Passed quiz certificate url'),
      'description' => t('The passed quiz certificate url, used in quiz_certificat_queue_processor queue'),
    ],
    'node_url' => [
      'name' => t('Quiz node url'),
      'description' => t('The quiz node url'),
    ],
    'user_first_name' => [
      'name' => t('Quiz user first name'),
      'description' => t('First name of the user who passed the quiz'),
    ],
    'user_last_name' => [
      'name' => t('Quiz user last name'),
      'description' => t('Last name of the user who passed the quiz'),
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
        case 'title':
          $replacements[$original] = isset($data['quiz_title']) && !empty($data['quiz_title']) ? $data['quiz_title'] : '';
          break;
        case 'certificate_url':
          $replacements[$original] = isset($data['quiz_certificat_url']) && !empty($data['quiz_certificat_url']) ? $data['quiz_certificat_url'] : '';
          break;
        case 'node_url':
          $replacements[$original] = isset($data['quiz_node_url']) && !empty($data['quiz_node_url']) ? $data['quiz_node_url'] : '';
          break;
        case 'user_first_name':
          $replacements[$original] = isset($data['quiz_user_first_name']) && !empty($data['quiz_user_first_name']) ? $data['quiz_user_first_name'] : '';
          break;
        case 'user_last_name':
          $replacements[$original] = isset($data['quiz_user_last_name']) && !empty($data['quiz_user_last_name']) ? $data['quiz_user_last_name'] : '';
          break;
      }
    }
  }
  return $replacements;
}