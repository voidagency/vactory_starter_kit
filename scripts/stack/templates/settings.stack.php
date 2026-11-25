<?php

/**
 * @file
 * Réglages d'une instance « stack » (scripts/stack du profil).
 *
 * Inclus en dernier par le settings.php de la copie du site : il l'emporte
 * sur les réglages copiés. Ne concerne que l'instance.
 */

$databases['default']['default'] = [
  'database' => 'db',
  'username' => 'db',
  'password' => 'db',
  'host' => 'db',
  'port' => 3306,
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

$settings['trusted_host_patterns'] = [
  '^.+\.ddev\.site$',
  '^localhost$',
  '^web$',
  '^127\.0\.0\.1$',
];
$settings['file_temp_path'] = '/tmp';
$settings['file_private_path'] = 'sites/default/private';

// Pas de téléchargement de traductions (comme la CI).
$config['locale.settings']['translation']['import_enabled'] = FALSE;
$config['locale.settings']['translation']['use_source'] = 'local';

// Front de l'instance (vide sans --front).
$settings['BASE_FRONTEND_URL'] = getenv('BASE_FRONTEND_URL') ?: NULL;
$settings['FRONTEND_CACHE_KEY'] = NULL;

// Secrets de l'instance, montés en lecture seule.
$stack_secrets_file = '/var/www/stack/secrets.env';
if (is_readable($stack_secrets_file)) {
  foreach (file($stack_secrets_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $stack_line) {
    if (str_starts_with($stack_line, 'FRONTEND_CACHE_KEY=')) {
      $settings['FRONTEND_CACHE_KEY'] = substr($stack_line, strlen('FRONTEND_CACHE_KEY='));
    }
  }
}
unset($stack_secrets_file, $stack_line);
