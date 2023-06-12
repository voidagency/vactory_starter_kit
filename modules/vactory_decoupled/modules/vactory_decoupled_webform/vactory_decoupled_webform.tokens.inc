<?php

/**
 * Implements hook_token_info().
 */
function vactory_decoupled_webform_token_info() {
  $decoupled_files_type = [
    'name' => t('Decoupled webform submissions files'),
    'description' => t('Tokens to generate webform submissions files urls under media subdomain'),
  ];

  $decoupled_files_tokens['decoupled_files'] = [
    'name' => t("Decoupled files"),
    'description' => t("Generate files URL using media subdomain, replace the '?' with the desired file element key"),
    'dynamic' => TRUE,
  ];
  return [
    'types'  => [
      'decoupled_webform_submission' => $decoupled_files_type,
    ],
    'tokens' => [
      'decoupled_webform_submission' => $decoupled_files_tokens
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_decoupled_webform_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  $token_service = Drupal::token();
  if ($type === 'decoupled_webform_submission') {
    /** @var \Drupal\webform\Entity\WebformSubmission $webform_submission */
    $webform_submission = $data['webform_submission'] ?? NULL;
    if (!empty($webform_submission)) {
      if ($value_tokens = $token_service->findWithPrefix($tokens, 'decoupled_files')) {
        foreach ($value_tokens as $value_token => $original) {
          $fid = $webform_submission->getElementData($value_token);
          $value = NULL;
          if (is_numeric($fid)) {
            $file = \Drupal\file\Entity\File::load($fid);
            if ($file) {
              $uri = $file->getFileUri();
              $value = Drupal::service('vacory_decoupled.media_file_manager')->getMediaAbsoluteUrl($uri);
            }
          }
          if ($value !== NULL) {
            $replacements[$original] = $value;
          }
        }
      }
    }
  }

  return $replacements;
}
