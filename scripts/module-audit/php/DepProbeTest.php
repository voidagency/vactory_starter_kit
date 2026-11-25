<?php

namespace Drupal\Tests\module_audit\Kernel;

use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Dependency probe of module-audit (Kernel, fresh prefixed tables).
 *
 * Env: DEP_MODULE (M), DEP_WITH (comma list of modules installed with M, may
 * be empty), DEP_OUT (json path). Installs system+user, then [DEP_WITH..., M]
 * through the real module installer (M's declared dependencies only), then
 * exercises M:
 * cache rebuild, router rebuild, src/ classes, M's services, plugin definitions
 * provided by M (+ instantiation of their class), entity/field definitions,
 * config entities provided by M, hook_cron of M. Every error is recorded with
 * where/class/message/file:line; nothing makes the test fail.
 *
 * Run by scripts/module-audit/lib/kprobe.py in a disposable stack instance,
 * never by the CI (outside the modules' tests/ directories).
 */
class DepProbeTest extends KernelTestBase {

  // phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema

  /**
   * Off: a schema error must not hide what the probe looks for.
   *
   * The probe measures missing modules, not config schemas; with strict
   * schema checking, the first schema error of M would stop the install.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  // phpcs:enable

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * Result written to DEP_OUT.
   *
   * @var array
   */
  private array $res = [];

  /**
   * Records an error.
   *
   * @param string $where
   *   Step or item being exercised.
   * @param \Throwable $t
   *   The error.
   */
  private function err(string $where, \Throwable $t): void {
    $msg = $t->getMessage();
    if ($t instanceof UnmetDependenciesException) {
      $parts = [];
      foreach ($t->getConfigObjects() as $name => $missing) {
        $parts[] = $name . ' (' . implode(', ', $missing) . ')';
      }
      $msg .= ' | unmet: ' . implode('; ', $parts);
    }
    $this->res['errors'][] = [
      'where' => $where,
      'error' => get_class($t) . ': ' . mb_substr($msg, 0, 1500),
      'at' => str_replace('/var/www/html/', '', $t->getFile())
      . ':' . $t->getLine(),
    ];
  }

  /**
   * Installs M and exercises it; writes the result to DEP_OUT.
   */
  public function testProbe(): void {
    $m = getenv('DEP_MODULE');
    $with = explode(',', (string) getenv('DEP_WITH'));
    $with = array_values(array_filter($with));
    $out = getenv('DEP_OUT');
    $this->res = [
      'module' => $m,
      'with' => $with,
      'errors' => [],
      'steps' => [],
    ];
    $t0 = microtime(TRUE);
    try {
      // Profile modules are only discoverable when the active profile path is
      // a prefix of theirs: 'vactory' = empty probe profile written by
      // kprobe.py (profiles/contrib/vactory), no profile config.
      $this->setInstallProfile(getenv('DEP_PROFILE') ?: 'vactory');
      $this->installEntitySchema('user');
      $this->installConfig(['system', 'user']);
      $this->installSchema('user', ['users_data']);
    }
    catch (\Throwable $t) {
      $this->err('setup', $t);
    }
    try {
      \Drupal::service('module_installer')->install([...$with, $m], TRUE);
      $this->res['steps'][] = 'install ok';
    }
    catch (\Throwable $t) {
      $this->err('install', $t);
    }
    $this->container = \Drupal::getContainer();
    $mh = \Drupal::moduleHandler();
    $this->res['enabled'] = $mh->moduleExists($m);
    $this->res['modules'] = array_keys($mh->getModuleList());
    if ($this->res['enabled']) {
      $this->exercise($m);
    }
    $this->res['time'] = round(microtime(TRUE) - $t0, 1);
    $json = json_encode(
      $this->res,
      JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    file_put_contents($out, $json);
    $this->assertTrue(TRUE);
  }

  /**
   * Exercises M's code paths, recording every error.
   *
   * @param string $m
   *   The module.
   */
  private function exercise(string $m): void {
    $steps = [
      'cache rebuild' => fn() => drupal_flush_all_caches(),
      'router rebuild' => fn() => \Drupal::service('router.builder')->rebuild(),
    ];
    foreach ($steps as $w => $f) {
      try {
        $f();
        $this->res['steps'][] = "$w ok";
      }
      catch (\Throwable $t) {
        $this->err($w, $t);
      }
    }
    $list = \Drupal::service('extension.list.module');
    $path = DRUPAL_ROOT . '/' . $list->getPath($m);
    $this->exerciseClasses($m, $path);
    $this->exerciseServices($m, $path);
    $this->exercisePlugins($m);
    $this->exerciseFields();
    $this->exerciseRoutes($m, $path);
    $this->exerciseConfigEntities($path);
    if (\Drupal::moduleHandler()->hasImplementations('cron', [$m])) {
      try {
        \Drupal::moduleHandler()->invoke($m, 'cron');
        $this->res['steps'][] = 'hook_cron ok';
      }
      catch (\Throwable $t) {
        $this->err('hook_cron', $t);
      }
    }
  }

  /**
   * Loads every class of M's src/ directory.
   *
   * @param string $m
   *   The module.
   * @param string $path
   *   Absolute path of the module.
   */
  private function exerciseClasses(string $m, string $path): void {
    if (!is_dir("$path/src")) {
      return;
    }
    $dir = new \RecursiveDirectoryIterator(
      "$path/src",
      \FilesystemIterator::SKIP_DOTS
    );
    $n = 0;
    foreach (new \RecursiveIteratorIterator($dir) as $f) {
      if (substr($f->getFilename(), -4) !== '.php') {
        continue;
      }
      $rel = substr($f->getPathname(), strlen("$path/src/"), -4);
      $fqcn = 'Drupal\\' . $m . '\\' . str_replace('/', '\\', $rel);
      $n++;
      try {
        if (!class_exists($fqcn) && !interface_exists($fqcn)
          && !trait_exists($fqcn)) {
          $this->res['errors'][] = [
            'where' => "class $fqcn",
            'error' => 'not loadable (class/interface/trait not found)',
          ];
        }
      }
      catch (\Throwable $t) {
        $this->err("class $fqcn", $t);
      }
    }
    $this->res['classes'] = $n;
  }

  /**
   * Instantiates every service of M's services.yml.
   *
   * @param string $m
   *   The module.
   * @param string $path
   *   Absolute path of the module.
   */
  private function exerciseServices(string $m, string $path): void {
    $sf = "$path/$m.services.yml";
    if (!is_file($sf)) {
      return;
    }
    $y = Yaml::parseFile($sf);
    $n = 0;
    foreach (($y['services'] ?? []) as $id => $def) {
      if (!is_array($def) || !empty($def['abstract'])
        || str_starts_with((string) $id, '_')) {
        continue;
      }
      $n++;
      try {
        \Drupal::service($id);
      }
      catch (\Throwable $t) {
        $this->err("service $id", $t);
      }
    }
    $this->res['services'] = $n;
  }

  /**
   * Reads a key of a plugin definition (array or object).
   *
   * @param mixed $d
   *   The plugin definition.
   * @param string $key
   *   Either 'provider' or 'class'.
   *
   * @return mixed
   *   The value, or NULL.
   */
  private function definitionValue($d, string $key) {
    if (is_array($d)) {
      return $d[$key] ?? NULL;
    }
    $getter = 'get' . ucfirst($key);
    return method_exists($d, $getter) ? $d->$getter() : NULL;
  }

  /**
   * Instantiates every plugin provided by M, in every plugin manager.
   *
   * @param string $m
   *   The module.
   */
  private function exercisePlugins(string $m): void {
    $np = 0;
    foreach (\Drupal::getContainer()->getServiceIds() as $sid) {
      if (!str_starts_with($sid, 'plugin.manager.')) {
        continue;
      }
      try {
        $pm = \Drupal::service($sid);
        if (!method_exists($pm, 'getDefinitions')) {
          continue;
        }
        foreach ($pm->getDefinitions() as $pid => $d) {
          if (($this->definitionValue($d, 'provider') ?? '') !== $m) {
            continue;
          }
          $np++;
          $cls = $this->definitionValue($d, 'class');
          $this->exercisePlugin($pm, $sid, (string) $pid, $cls);
        }
      }
      catch (\Throwable $t) {
        $this->err("plugin manager $sid", $t);
      }
    }
    $this->res['plugins'] = $np;
  }

  /**
   * Instantiates one plugin.
   *
   * @param object $pm
   *   The plugin manager.
   * @param string $sid
   *   Its service id.
   * @param string $pid
   *   The plugin id.
   * @param mixed $cls
   *   The plugin class, if any.
   */
  private function exercisePlugin($pm, string $sid, string $pid, $cls): void {
    try {
      if ($cls && !class_exists($cls)) {
        $this->res['errors'][] = [
          'where' => "plugin $sid:$pid",
          'error' => "class $cls not loadable",
        ];
      }
      elseif ($cls && method_exists($pm, 'createInstance')) {
        // Instantiation runs create()/__construct(): services of other
        // modules are injected here.
        $pm->createInstance($pid, []);
      }
    }
    catch (\Throwable $t) {
      $this->err("plugin $sid:$pid", $t);
    }
  }

  /**
   * Builds the field definitions of every fieldable entity type and bundle.
   */
  private function exerciseFields(): void {
    try {
      $etm = \Drupal::entityTypeManager();
      $efm = \Drupal::service('entity_field.manager');
      $bundles = \Drupal::service('entity_type.bundle.info');
      $fieldable = 'Drupal\Core\Entity\FieldableEntityInterface';
      foreach ($etm->getDefinitions() as $id => $et) {
        if (!$et->entityClassImplements($fieldable)) {
          continue;
        }
        try {
          $efm->getFieldStorageDefinitions($id);
          foreach (array_keys($bundles->getBundleInfo($id)) as $b) {
            $efm->getFieldDefinitions($id, $b);
          }
        }
        catch (\Throwable $t) {
          $this->err("fields $id", $t);
        }
      }
      $this->res['steps'][] = 'entity/field definitions done';
    }
    catch (\Throwable $t) {
      $this->err('entity definitions', $t);
    }
  }

  /**
   * Builds M's forms and instantiates its controllers.
   *
   * @param string $m
   *   The module.
   * @param string $path
   *   Absolute path of the module.
   */
  private function exerciseRoutes(string $m, string $path): void {
    $rf = "$path/$m.routing.yml";
    if (!is_file($rf)) {
      return;
    }
    $nr = 0;
    foreach ((Yaml::parseFile($rf) ?: []) as $rid => $route) {
      $def = is_array($route) ? ($route['defaults'] ?? []) : [];
      $controller = (string) ($def['_controller'] ?? '');
      try {
        if (!empty($def['_form']) && class_exists($def['_form'])) {
          $nr++;
          \Drupal::formBuilder()->getForm($def['_form']);
        }
        elseif (str_contains($controller, '::')) {
          $nr++;
          $class = explode('::', ltrim($controller, '\\'))[0];
          \Drupal::service('class_resolver')->getInstanceFromDefinition($class);
        }
      }
      catch (\Throwable $t) {
        $this->err("route $rid", $t);
      }
    }
    $this->res['routes'] = $nr;
  }

  /**
   * Loads the config entities installed by M (entity class, plugin bags).
   *
   * @param string $path
   *   Absolute path of the module.
   */
  private function exerciseConfigEntities(string $path): void {
    try {
      $store = \Drupal::service('config.storage');
      $cm = \Drupal::service('config.manager');
      $files = array_merge(
        glob("$path/config/install/*.yml") ?: [],
        glob("$path/config/optional/*.yml") ?: []
      );
      foreach ($files as $f) {
        $name = basename($f, '.yml');
        if (!$store->exists($name)) {
          continue;
        }
        try {
          $this->loadConfigEntity($cm, $name);
        }
        catch (\Throwable $t) {
          $this->err("config $name", $t);
        }
      }
    }
    catch (\Throwable $t) {
      $this->err('config entities', $t);
    }
  }

  /**
   * Loads one config entity and iterates its plugin collections.
   *
   * @param object $cm
   *   The config manager.
   * @param string $name
   *   The config name.
   */
  private function loadConfigEntity($cm, string $name): void {
    if (!$cm->getEntityTypeIdByName($name)) {
      return;
    }
    $e = $cm->loadConfigEntityByName($name);
    if (!$e || !method_exists($e, 'getPluginCollections')) {
      return;
    }
    foreach ($e->getPluginCollections() as $collection) {
      // Iterating instantiates each plugin of the collection.
      iterator_to_array($collection);
    }
  }

}
