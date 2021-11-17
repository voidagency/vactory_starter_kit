<?php

/**
 * @file
 * Module related tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function vactory_wisepops_token_info() {
  $type_vactory_wisepops = [
    'name' => t('Vactory - WisePops'),
    'description' => t("Offre des tokens pour le module vactory wisepops."),
  ];
  $user['age'] = [
    'name' => t("Age"),
  ];
  return [
    'types' => [
      'v_wisepops' => $type_vactory_wisepops,
    ],
    'tokens' => [
      'v_wisepops' => $user,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_wisepops_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'v_wisepops') {
    $current_user = \Drupal::currentUser();
    $user = NULL;
    if ($current_user->isAuthenticated()) {
      $user = User::load($current_user->id());
    }
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'age':
          $age = '';
          if (isset($user)) {
            $date_of_birth = !empty($user->get('field_date_of_birth')->getValue()) ? $user->get('field_date_of_birth')->getValue()[0]['value'] : '';
            if (!empty($date_of_birth)) {
              $bday = new DateTime($date_of_birth);
              $today = new Datetime(date('y-m-d'));
              $diff = $today->diff($bday);
              $age = $diff->y;
            }
          }
          $replacements[$original] = $age;
          break;

        default:
          $replacements[$original] = '';
          break;

      }
    }
  }
  return $replacements;
}
