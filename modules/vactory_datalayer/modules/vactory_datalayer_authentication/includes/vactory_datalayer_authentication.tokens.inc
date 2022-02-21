<?php

/**
 * @file
 * Module related tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function vactory_datalayer_authentication_token_info() {
  $type_vactory_datalayer_authentication = [
    'name'        => t('Vactory - DataLayer Authentication'),
    'description' => t("Offre des tokens pour le module dataLayer authentication."),
  ];

  $config = \Drupal::config('vactory_datalayer_authentication.settings');
  $tokens = json_decode($config->get('vactory_datalayer_authentication_config'), TRUE);
  $user = [];
  if (is_array($tokens)) {
    foreach ($tokens as $token) {
      $user[$token['token_key']] = [
        'name' => t($token['token_name']),
      ];
    }
  }

  return [
    'types'  => [
      'v_datalayer_auth' => $type_vactory_datalayer_authentication,
    ],
    'tokens' => [
      'v_datalayer_auth' => $user,
    ],
    '#cache' => [
      'tags' =>  ['config:vactory_datalayer_authentication.settings']
    ],

  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_datalayer_authentication_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'v_datalayer_auth') {

    $current_user = \Drupal::currentUser();
    $user = NULL;
    if ($current_user->isAuthenticated()) {
      $user = User::load($current_user->id());
    }

    $config = \Drupal::config('vactory_datalayer_authentication.settings');
    $tokens_config = json_decode($config->get('vactory_datalayer_authentication_config'), TRUE);

    if ($tokens_config) {
      foreach ($tokens as $name => $original) {
        $key = array_search($name, array_column($tokens_config, 'token_key'));
        if (!is_numeric($key)){
          unset($tokens[$name]);
        }
      }

      foreach ($tokens as $name => $original) {
        $key = array_search($name, array_column($tokens_config, 'token_key'));
        if ($key && isset($user)) {
          if (!empty($user->get($tokens_config[$key]['field_machine_name']))) {
            if ($tokens_config[$key]['cible'] == 'value'){
              $value = $user->get($tokens_config[$key]['field_machine_name'])->value;
            }
            if ($tokens_config[$key]['cible'] == 'target_id'){
              $value = $user->get($tokens_config[$key]['field_machine_name'])->target_id;
            }
            if ($tokens_config[$key]['cible'] == 'target_id' && isset($tokens_config[$key]['is_taxonomy']) &&  $tokens_config[$key]['is_taxonomy'] == "true" ){
              $value = Term::load($user->get($tokens_config[$key]['field_machine_name'])->target_id)->getName();
            }
          }
          $replacements[$original] = $value;
        }
      }
    }
  }
  return $replacements;
}
