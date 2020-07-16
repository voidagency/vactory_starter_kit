<?php

/**
 * @file
 * Main hooks.
 */

use Drupal\node\Entity\Node;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\user\Entity\User;

/**
 * Implements hook_form_alter().
 */
function vactory_form_alter(&$form, &$form_state, $form_id) {
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
function vactory_install_tasks_alter(&$tasks, $install_state) {
  $tasks['install_select_language']['display'] = FALSE;
  $tasks['install_select_language']['run'] = INSTALL_TASK_SKIP;
  $tasks['install_finished']['function'] = 'vactory_after_install_finished';
}

/**
 * Implements hook_install_tasks().
 */
function vactory_install_tasks(&$install_state) {
  return [
    'vactory_configure_site' => [
      'display_name' => t('Configure site'),
      'display'      => TRUE,
    ],
  ];
}

/**
 * Config site apparences and create front page node.
 *
 * @param array $install_state
 *   The current install state.
 */
function vactory_configure_site(array &$install_state) {
  // Populate the default shortcut set.
  $shortcut = Shortcut::create([
    'shortcut_set' => 'default',
    'title'        => t('Add content'),
    'weight'       => -20,
    'link'         => ['uri' => 'internal:/node/add'],
  ]);
  $shortcut->save();

  $shortcut = Shortcut::create([
    'shortcut_set' => 'default',
    'title'        => t('All content'),
    'weight'       => -19,
    'link'         => ['uri' => 'internal:/admin/content'],
  ]);
  $shortcut->save();

  // Assign user 1 the "administrator" role.
  $user = User::load(1);
  $user->roles[] = 'administrator';
  $user->save();

  \Drupal::configFactory()
    ->getEditable('vactory_theme.settings')
    ->set('logo.use_default', TRUE);

  // Enable the admin theme.
  \Drupal::configFactory()
    ->getEditable('node.settings')
    ->set('use_admin_theme', TRUE)
    ->save(TRUE);

  // Enable the admin theme.
  \Drupal::configFactory()
    ->getEditable('xmlsitemap.settings')
    ->set('disable_cron_regeneration', TRUE)
    ->save(TRUE);

  // Set default theme to "Vactory_theme".
  \Drupal::configFactory()
    ->getEditable('system.theme')
    ->set('default', 'vactory_theme')
    ->save();

  // Set default admin theme to "Seven".
  \Drupal::configFactory()
    ->getEditable('system.theme')
    ->set('admin', 'seven')
    ->save();

  \Drupal::service('theme_handler')->reset();
  \Drupal::service('theme_handler')->rebuildThemeData();
  \Drupal::service('theme_handler')->refreshInfo();

  // Create Homepage node.
  $node = Node::create([
    'type'     => 'vactory_page',
    'title'    => 'Homepage',
    'langcode' => 'en',
    'uid'      => 1,
  ]);
  $node->save();

  // Set front page to "node" - HP.
  \Drupal::configFactory()
    ->getEditable('system.site')
    ->set('page.front', '/node/' . $node->id())
    ->save(TRUE);

  // Run cron.
  \Drupal::service('cron')->run();

  // Run a complete cache flush.
  drupal_flush_all_caches();
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
function vactory_after_install_finished(array &$install_state) {
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
