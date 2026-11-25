<?php

/**
 * @file
 * Réglages d'une instance « stack », lancé par « drush php:script ».
 *
 * Usage : drush php:script /var/www/stack/tool/setup.php -- <étape>...
 * Étapes : mail, oauth.
 *
 * Lit les secrets dans /var/www/stack/secrets.env et ne les affiche jamais.
 * Idempotent : relancé à chaque « stack up ».
 */

use Drupal\consumers\Entity\Consumer;
use Drupal\user\Entity\User;

$stack_steps = array_slice($extra ?? [], 0);
if (empty($stack_steps)) {
  $stack_steps = ['mail', 'oauth'];
}

/**
 * Lit secrets.env (clé=valeur).
 */
function stack_secrets(): array {
  $values = [];
  $file = '/var/www/stack/secrets.env';
  if (!is_readable($file)) {
    throw new \RuntimeException('secrets.env illisible dans le conteneur.');
  }
  foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#' || !str_contains($line, '=')) {
      continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $values[trim($key)] = $value;
  }
  return $values;
}

/**
 * Envoi des mails vers le Mailpit de ddev (SMTP localhost:1025, sans TLS).
 */
function stack_setup_mail(): void {
  $module_handler = \Drupal::moduleHandler();
  $done = [];
  if ($module_handler->moduleExists('symfony_mailer')) {
    $storage = \Drupal::entityTypeManager()->getStorage('mailer_transport');
    $transport = $storage->load('stack_mailpit');
    if (!$transport) {
      $transport = $storage->create([
        'id' => 'stack_mailpit',
        'label' => 'Mailpit (stack)',
        'plugin' => 'smtp',
      ]);
    }
    $transport->set('configuration', [
      'user' => '',
      'pass' => '',
      'host' => 'localhost',
      'port' => 1025,
      'query' => ['verify_peer' => FALSE, 'local_domain' => ''],
    ]);
    $transport->save();
    $transport->setAsDefault();
    $done[] = 'symfony_mailer : transport stack_mailpit par défaut';
  }
  if ($module_handler->moduleExists('smtp')) {
    \Drupal::configFactory()->getEditable('smtp.settings')
      ->set('smtp_on', TRUE)
      ->set('smtp_host', 'localhost')
      ->set('smtp_port', '1025')
      ->set('smtp_protocol', 'standard')
      ->set('smtp_username', '')
      ->set('smtp_password', '')
      ->save();
    $done[] = 'smtp : localhost:1025';
  }
  if (!$done) {
    // Sans module d'envoi : PHP mail() de ddev va déjà dans Mailpit.
    $done[] = 'mail() PHP de ddev (Mailpit)';
  }
  print 'mail: ' . implode(' ; ', $done) . PHP_EOL;
}

/**
 * Clés de signature, clients OAuth et comptes de test.
 */
function stack_setup_oauth(): void {
  $secrets = stack_secrets();
  $required = [
    'STACK_FRONT_CLIENT_ID',
    'STACK_FRONT_CLIENT_SECRET',
    'STACK_ADMIN_CLIENT_ID',
    'STACK_ADMIN_CLIENT_SECRET',
    'STACK_USER_PASSWORD',
    'STACK_EDITOR_PASSWORD',
  ];
  foreach ($required as $key) {
    if (empty($secrets[$key])) {
      throw new \RuntimeException("Valeur manquante dans secrets.env : $key");
    }
  }
  if (!\Drupal::moduleHandler()->moduleExists('simple_oauth')) {
    throw new \RuntimeException('simple_oauth absent : relancer avec --no-oauth.');
  }

  \Drupal::configFactory()->getEditable('simple_oauth.settings')
    ->set('public_key', '/var/www/stack/keys/public.key')
    ->set('private_key', '/var/www/stack/keys/private.key')
    ->save();

  // Comptes de test.
  $accounts = [
    'stack_user' => [$secrets['STACK_USER_PASSWORD'], []],
    'stack_editor' => [$secrets['STACK_EDITOR_PASSWORD'], ['administrator']],
  ];
  foreach ($accounts as $name => [$password, $roles]) {
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $name]);
    $user = $users ? reset($users) : User::create([
      'name' => $name,
      'mail' => $name . '@example.com',
    ]);
    $user->setPassword($password);
    $user->activate();
    foreach ($roles as $role) {
      $user->addRole($role);
    }
    $user->save();
  }

  // Clients OAuth. Avec simple_oauth, les rôles d'un client sont donnés à
  // tous les jetons qu'il émet : le client du front n'en a aucun.
  $clients = [
    'front' => [
      $secrets['STACK_FRONT_CLIENT_ID'],
      $secrets['STACK_FRONT_CLIENT_SECRET'],
      [],
      'Front (stack)',
    ],
    'admin' => [
      $secrets['STACK_ADMIN_CLIENT_ID'],
      $secrets['STACK_ADMIN_CLIENT_SECRET'],
      ['administrator'],
      'Administration (stack)',
    ],
  ];
  $storage = \Drupal::entityTypeManager()->getStorage('consumer');
  foreach ($clients as [$client_id, $secret, $roles, $label]) {
    $found = $storage->loadByProperties(['client_id' => $client_id]);
    $consumer = $found ? reset($found) : Consumer::create([
      'client_id' => $client_id,
      'label' => $label,
    ]);
    $consumer->set('label', $label);
    $consumer->set('secret', $secret);
    $consumer->set('confidential', TRUE);
    $consumer->set('roles', array_map(fn($role) => ['target_id' => $role], $roles));
    $consumer->save();
  }

  // Connexion locale sans captcha : la route de connexion du front est
  // retirée des routes protégées de l'instance.
  $config = \Drupal::configFactory()->getEditable('vactory_decoupled.settings');
  $routes = (string) $config->get('routes');
  if ($routes !== '') {
    $kept = array_filter(preg_split('/\R/', $routes), fn($route) => !str_contains($route, 'oauth/login-token'));
    $config->set('routes', implode("\n", $kept))->save();
  }
  print 'oauth: clés, 2 clients, stack_user, stack_editor' . PHP_EOL;
}

foreach ($stack_steps as $stack_step) {
  match ($stack_step) {
    'mail' => stack_setup_mail(),
    'oauth' => stack_setup_oauth(),
    default => throw new \InvalidArgumentException("Étape inconnue : $stack_step"),
  };
}
