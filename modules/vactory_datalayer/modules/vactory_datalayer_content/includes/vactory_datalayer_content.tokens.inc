<?php

/**
 * @file
 * Module related tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_token_info().
 */
function vactory_datalayer_content_token_info() {
  $type_vactory_datalayer_content = [
    'name' => t('Vactory - DataLayer Content'),
    'description' => t("Offre des tokens pour le module vactory dataLayer Content."),
  ];

  $config = \Drupal::config('vactory_datalayer_content.settings');
  $tokens = json_decode($config->get('vactory_datalayer_content_config'), TRUE);
  $content = [];
  if ($tokens) {
    foreach ($tokens as $token) {
      $content[$token['token_key']] = [
        'name' => t($token['token_name']),
      ];
    }
  }

  return [
    'types' => [
      'v_datalayer_content' => $type_vactory_datalayer_content,
    ],
    'tokens' => [
      'v_datalayer_content' => $content,
    ],
    '#cache' => [
      'tags' =>  ['config:vactory_datalayer_content.settings']
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_datalayer_content_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'v_datalayer_content') {
    $node = \Drupal::routeMatch()->getParameter('node');

    $config = \Drupal::config('vactory_datalayer_content.settings');
    $tokens_config = json_decode($config->get('vactory_datalayer_content_config'), TRUE);

    foreach ($tokens as $name => $original) {
      $key = array_search($name, array_column($tokens_config, 'token_key'));
      if (!is_numeric($key)){
        unset($tokens[$name]);
      }
    }

    foreach ($tokens as $name => $original) {

      $key = array_search($name, array_column($tokens_config, 'token_key'));
      if ($node instanceof NodeInterface) {
        if (!empty($node->get($tokens_config[$key]['field_machine_name']))) {
          if ($tokens_config[$key]['cible'] == 'value'){
            $value = $node->get($tokens_config[$key]['field_machine_name'])->value;
          }
          if ($tokens_config[$key]['cible'] == 'target_id'){
            $value = $node->get($tokens_config[$key]['field_machine_name'])->target_id;
          }
          if ($tokens_config[$key]['cible'] == 'target_id' && isset($tokens_config[$key]['is_taxonomy']) &&  $tokens_config[$key]['is_taxonomy'] == "true" ){
            $value = Term::load($node->get($tokens_config[$key]['field_machine_name'])->target_id)->getName();
          }
        }
        $replacements[$original] = $value;
      }
    }

  }
  return $replacements;
}
