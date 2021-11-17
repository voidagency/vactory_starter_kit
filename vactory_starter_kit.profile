<?php

/**
 * @file
 * Main hooks.
 */

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Implements hook_form_alter().
 */
function vactory_starter_kit_form_alter(&$form, &$form_state, $form_id) {
  switch ($form_id) {
    case 'install_configure_form':

      // Pre-populate site email address.
      $form['site_information']['site_name']['#default_value'] = 'Vactory';
      $form['site_information']['site_mail']['#default_value'] = 'admin@void.fr';

      // Pre-populate username.
      $form['admin_account']['account']['name']['#default_value'] = 'admin';

      // Pre-populate admin email.
      $form['admin_account']['account']['mail']['#default_value'] = 'admin@void.fr';

      // Disable notifications.
      $form['update_notifications']['update_status_module']['#default_value'][1] = 0;
      break;
  }
}

/**
 * Implements hook_install_tasks_alter().
 */
function vactory_starter_kit_install_tasks_alter(&$tasks, $install_state) {
  $tasks['install_select_language']['display'] = FALSE;
  $tasks['install_select_language']['run'] = INSTALL_TASK_SKIP;
  $tasks['install_finished']['function'] = 'vactory_starter_kit_after_install_finished';
}

/**
 * Implements hook_install_tasks().
 */
function vactory_starter_kit_install_tasks(&$install_state) {
  return [
    'vactory_starter_kit_configure_multilingual' => [
      'display_name' => t('Configure multilingual'),
      'display'      => TRUE,
      'type'         => 'batch',
    ],
  ];
}

/**
 * Batch job to configure multilingual components.
 *
 * @param array $install_state
 *   The current install state.
 *
 * @return array
 *   The batch job definition.
 */
function vactory_starter_kit_configure_multilingual(array &$install_state) {
  $batch = [];

  // Add all selected languages.
  foreach (['fr', 'ar'] as $language_code) {
    $batch['operations'][] = [
      'vactory_configure_language_and_fetch_traslation',
      (array) $language_code,
    ];
  }

  return $batch;
}

/**
 * Batch function to add selected languages then fetch all translation.
 *
 * @param string|array $language_code
 *   Language code to install and fetch all translation.
 */
function vactory_configure_language_and_fetch_traslation($language_code) {
  ConfigurableLanguage::createFromLangcode($language_code)->save();
}

/**
 * Factory after install finished.
 *
 * @param array $install_state
 *   The current install state.
 *
 * @return array
 *   A renderable array with a redirect header.
 */
function vactory_starter_kit_after_install_finished(array &$install_state) {
  global $base_url;

  // After install direction.
  $after_install_direction = $base_url . '/?welcome';

  install_finished($install_state);
  $output = [];

  // Clear all messages.
  /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
  $messenger = \Drupal::service('messenger');
  $messenger->deleteAll();

  // Success message.
  $messenger->addMessage(t('Congratulations, you have installed Factory 8!'));

  // Run a complete cache flush.
  drupal_flush_all_caches();

  $output = [
    '#title'    => t('Vactory'),
    'info'      => [
      '#markup' => t('<p>Congratulations, you have installed Factory 8!</p><p>If you are not redirected to the front page in 5 seconds, Please <a href="@url">click here</a> to proceed to your installed site.</p>', [
        '@url' => $after_install_direction,
      ]),
    ],
    '#attached' => [
      'http_header' => [
        ['Cache-Control', 'no-cache'],
      ],
    ],
  ];

  $meta_redirect = [
    '#tag'        => 'meta',
    '#attributes' => [
      'http-equiv' => 'refresh',
      'content'    => '5;url=' . $after_install_direction,
    ],
  ];
  $output['#attached']['html_head'][] = [$meta_redirect, 'meta_redirect'];

  return $output;
}
