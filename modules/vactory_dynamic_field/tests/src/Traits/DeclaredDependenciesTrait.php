<?php

namespace Drupal\Tests\vactory_dynamic_field\Traits;

use Drupal\Core\Config\FileStorage;

/**
 * Installs a module of the profile with only its declared dependencies.
 *
 * Projects enable the modules of the profile one by one (drush en,
 * Features): a module must install with the modules its info.yml declares,
 * whatever else the profile installs (Redmine #71511).
 *
 * For Kernel tests whose $modules are system and user only.
 */
trait DeclaredDependenciesTrait {

  /**
   * Prepares a site that sees the modules of the profile, none installed.
   */
  protected function setUpProfileSite(): void {
    // The modules of the profile are only discovered when the profile is the
    // install profile, as on the sites of the projects.
    $this->setInstallProfile('vactory_starter_kit');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'user']);
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Installs modules as "drush en" does: with their declared dependencies.
   *
   * @param string[] $modules
   *   The modules to install.
   */
  protected function installWithDependencies(array $modules): void {
    $this->container->get('module_installer')->install($modules);
    $this->container = \Drupal::getContainer();
  }

  /**
   * Returns the shipped config that needs a module the module does not need.
   *
   * A module is needed when the module is, or requires it in its info.yml,
   * directly or through its dependencies.
   *
   * @param string $module
   *   The module.
   * @param string $directory
   *   The config directory: install or optional.
   *
   * @return string[][]
   *   The missing modules, keyed by config name.
   */
  protected function configNeedingUndeclaredModules(string $module, string $directory): array {
    $list = $this->container->get('extension.list.module')->reset();
    $known = array_keys($list->getList());
    $needed = array_keys($list->get($module)->requires);
    $needed[] = $module;
    $storage = $this->shippedConfigStorage($module, $directory);
    $unmet = [];
    foreach ($storage->listAll() as $name) {
      $data = $storage->read($name);
      $modules = array_merge(
        $data['dependencies']['module'] ?? [],
        $data['dependencies']['enforced']['module'] ?? [],
        // The provider of the config: field.storage.* is a config of field.
        array_intersect([strstr($name, '.', TRUE)], $known)
      );
      $missing = array_values(array_unique(array_diff($modules, $needed)));
      if ($missing) {
        $unmet[$name] = $missing;
      }
    }
    return $unmet;
  }

  /**
   * Returns the names of the config shipped in a directory of a module.
   *
   * @param string $module
   *   The module.
   * @param string $directory
   *   The config directory: install or optional.
   *
   * @return string[]
   *   The config names.
   */
  protected function shippedConfig(string $module, string $directory): array {
    return $this->shippedConfigStorage($module, $directory)->listAll();
  }

  /**
   * Returns a config directory of a module.
   *
   * @param string $module
   *   The module.
   * @param string $directory
   *   The config directory: install or optional.
   *
   * @return \Drupal\Core\Config\FileStorage
   *   The storage of the directory.
   */
  protected function shippedConfigStorage(string $module, string $directory): FileStorage {
    $path = $this->container->get('extension.list.module')->getPath($module);
    return new FileStorage($this->root . '/' . $path . '/config/' . $directory);
  }

  /**
   * Asserts that a config object exists, or not.
   *
   * @param string $name
   *   The config name.
   * @param bool $exists
   *   Whether the config must exist.
   */
  protected function assertConfigExists(string $name, bool $exists = TRUE): void {
    $this->assertSame(
      $exists,
      !$this->config($name)->isNew(),
      $name . ($exists ? ' is installed.' : ' is not installed.')
    );
  }

}
