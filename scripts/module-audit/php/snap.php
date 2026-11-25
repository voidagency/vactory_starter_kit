<?php

/**
 * @file
 * Module-audit: dumps a JSON picture of the site state.
 *
 * Run with drush php:script.
 *
 * Output path: first extra argument (default: stdout). Read-only.
 */

use Drupal\Core\Entity\ContentEntityTypeInterface;

$out = $extra[0] ?? 'php://stdout';
$db = \Drupal::database();
$snap = [];

$snap['modules'] = array_keys(\Drupal::config('core.extension')->get('module') ?: []);
sort($snap['modules']);

// Tables.
$snap['tables'] = array_values(array_map('strval', $db->query('SHOW TABLES')->fetchCol()));
sort($snap['tables']);

// Config names + hash; role permissions in clear.
$cfg = [];
foreach ($db->query("SELECT name, data FROM {config} WHERE collection = ''") as $r) {
  $cfg[$r->name] = substr(md5($r->data), 0, 12);
}
ksort($cfg);
$snap['config'] = $cfg;
$snap['config_collections'] = $db->query("SELECT collection, COUNT(*) c FROM {config} WHERE collection <> '' GROUP BY collection")->fetchAllKeyed();
$perms = [];
foreach (\Drupal::configFactory()->listAll('user.role.') as $n) {
  $p = \Drupal::config($n)->get('permissions') ?: [];
  sort($p);
  $perms[substr($n, 10)] = $p;
}
$snap['role_permissions'] = $perms;

// key_value: collection => names.
$kv = [];
foreach ($db->query('SELECT collection, name FROM {key_value} ORDER BY collection, name') as $r) {
  $kv[$r->collection][] = $r->name;
}
$snap['key_value'] = $kv;
$snap['key_value_expire'] = $db->query('SELECT collection, COUNT(*) FROM {key_value_expire} GROUP BY collection')->fetchAllKeyed();

// Queues.
$snap['queue'] = $db->schema()->tableExists('queue') ? $db->query('SELECT name, COUNT(*) FROM {queue} GROUP BY name')->fetchAllKeyed() : [];

// Content entities: id => bundle|label ; revision counts.
$ents = [];
$revs = [];
$etm = \Drupal::entityTypeManager();
foreach ($etm->getDefinitions() as $id => $def) {
  if (!$def instanceof ContentEntityTypeInterface) {
    continue;
  }
  try {
    $storage = $etm->getStorage($id);
    $ids = $storage->getQuery()->accessCheck(FALSE)->range(0, 20000)->execute();
    $rows = [];
    foreach (array_chunk($ids, 200, TRUE) as $chunk) {
      foreach ($storage->loadMultiple($chunk) as $e) {
        $label = '';
        try {
          $label = (string) $e->label();
        }
        catch (\Throwable $t) {
        }
        $rows[$e->id()] = $e->bundle() . '|' . mb_substr($label, 0, 60);
      }
      $storage->resetCache();
    }
    $ents[$id] = $rows;
    if ($def->isRevisionable() && ($rt = $def->getRevisionTable()) && $db->schema()->tableExists($rt)) {
      $revs[$id] = (int) $db->query("SELECT COUNT(*) FROM {" . $rt . "}")->fetchField();
    }
  }
  catch (\Throwable $t) {
    $ents[$id] = ['__error' => get_class($t) . ': ' . mb_substr($t->getMessage(), 0, 300)];
  }
}
$snap['entities'] = $ents;
$snap['revisions'] = $revs;

// Menu tree (static + content links).
$snap['menu_tree'] = $db->schema()->tableExists('menu_tree')
  ? $db->query('SELECT id, CONCAT(menu_name, \'|\', title) FROM {menu_tree} ORDER BY id')->fetchAllKeyed() : [];

// Routes.
$snap['routes'] = $db->query('SELECT name, path FROM {router} ORDER BY name')->fetchAllKeyed();

// hook_cron implementations.
$cron = new \ArrayObject();
\Drupal::moduleHandler()->invokeAllWith(
  'cron',
  fn(callable $hook, string $module) => $cron->append($module)
);
$snap['cron_hooks'] = $cron->getArrayCopy();

// Files in the public dir (no generated css/js/styles/php).
$files = [];
$pub = \Drupal::service('file_system')->realpath('public://');
if ($pub && is_dir($pub)) {
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pub, FilesystemIterator::SKIP_DOTS));
  foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($pub) + 1);
    if (preg_match('#^(css|js|php|styles|translations|languages|twig)/#', $rel)) {
      continue;
    }
    $files[] = $rel;
  }
}
sort($files);
$snap['public_files'] = $files;

// Watchdog high-water mark.
$snap['watchdog_max_wid'] = $db->schema()->tableExists('watchdog') ? (int) $db->query('SELECT MAX(wid) FROM {watchdog}')->fetchField() : NULL;
$snap['front_page'] = \Drupal::config('system.site')->get('page.front');

file_put_contents($out, json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "snap ok " . $out . "\n";
