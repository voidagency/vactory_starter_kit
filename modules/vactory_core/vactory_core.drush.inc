<?php

/**
 * @file
 * Main Drush file for Vactory core.
 */

/**
 * Implements hook_drush_command().
 */
function vactory_core_drush_command() {
  return [
    'vactory-switch-environment' => [
      'aliases'     => ['vse'],
      'callback'    => '_vactory_core_switch_environment',
      'description' => 'This tool is aimed at simplifying the process of enabling/disabling modules and altering configuration when switching between different environments, e.g. Production/Staging/Development.',
      'arguments'   => [
        'environment' => 'The environment to switch to. Possible values: development, production',
      ],
      'examples'    => [
        'drush vactory-switch-environment production'  => dt('Activate the production environment'),
        'drush vactory-switch-environment development' => dt('Activate the development environment'),
      ],
    ],
  ];
}

/**
 * Drush command logic.
 *
 * @param string $environment
 *   The environment to switch to. Possible values: development, production.
 */
function _vactory_core_switch_environment($environment = '') {
  include_once \Drupal::root() . '/core/includes/install.inc';

  if (!in_array($environment, ['development', 'production'])) {
    drush_log(dt('Missing argument: development, production.'), 'error');
    return;
  }

  // Enable/Disable modules based on environment.
  $modules = [
    'devel',
    'devel_generate',
    'webprofiler',
    'features_ui',
    'modules_weight',
    'realistic_dummy_content',
    'security_review',
    'views_ui',
    'twig_vardumper',
    'stage_file_proxy',
    'seckit',
    'vactory_devtools',
  ];

  if ($environment === 'development') {
    \Drupal::service('module_installer')->install($modules);
  }
  else {
    if ($environment === 'production') {
      \Drupal::service('module_installer')->uninstall($modules);
    }
  }

  // Update settings.php based on environment.
  $settings['vactory_environment'] = (object) [
    'value' => $environment,
    'required' => TRUE,
  ];;
  drupal_rewrite_settings($settings);

  // Run a complete cache flush.
  drupal_flush_all_caches();
}
