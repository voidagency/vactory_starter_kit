<?php

namespace Drupal\Tests\vactory_field\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\vactory_dynamic_field\Traits\DeclaredDependenciesTrait;

/**
 * Vactory_field installs with its declared dependencies only (#71511).
 *
 * Its field storages need comment, datetime_range, menu_link_content, node,
 * options and taxonomy: without them Drupal refused "drush en vactory_field"
 * (UnmetDependenciesException).
 *
 * @group vactory_field
 * @group dependencies
 */
class VactoryFieldInstallTest extends KernelTestBase {

  use DeclaredDependenciesTrait;

  /**
   * Modules the shipped field storages need.
   */
  const STORAGE_MODULES = [
    'comment',
    'datetime_range',
    'menu_link_content',
    'node',
    'options',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  // phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema

  /**
   * Off: this test checks dependencies, not config schemas.
   *
   * With the profile as install profile, its config/install overrides the
   * config of the modules, and system.cron of the profile already fails the
   * schema (logging: 1, a boolean in the schema) before the module installs.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  // phpcs:enable

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpProfileSite();
  }

  /**
   * The shipped config needs only modules vactory_field requires.
   */
  public function testShippedConfigNeedsDeclaredModules(): void {
    $unmet = $this->configNeedingUndeclaredModules('vactory_field', 'install');
    $this->assertSame([], $unmet);
    $this->assertSame([], $this->shippedConfig('vactory_field', 'optional'));
  }

  /**
   * The module and all its config install with the declared modules.
   */
  public function testInstallWithDeclaredDependencies(): void {
    $requires = $this->container->get('extension.list.module')
      ->get('vactory_field')->requires;
    $this->installWithDependencies(array_keys($requires));
    $this->removeProfileCopies();
    $this->installWithDependencies(['vactory_field']);

    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('vactory_field'));
    foreach (self::STORAGE_MODULES as $module) {
      $this->assertTrue($module_handler->moduleExists($module), "$module is installed with vactory_field.");
    }
    $installed = $this->shippedConfig('vactory_field', 'install');
    $this->assertNotEmpty($installed);
    foreach ($installed as $name) {
      $this->assertConfigExists($name);
    }
  }

  /**
   * Removes the copies of the config of vactory_field the profile installed.
   *
   * The profile repeats media types, fields and displays of vactory_field in
   * its config/optional: on a site of the profile, Drupal installs them with
   * media, and vactory_field then refuses to install (PreExistingConfig,
   * Redmine #71508). Only those copies may be there before vactory_field.
   */
  protected function removeProfileCopies(): void {
    $existing = array_values(array_filter(
      $this->shippedConfig('vactory_field', 'install'),
      fn ($name) => !$this->config($name)->isNew()
    ));
    $profile_path = $this->container->get('extension.list.profile')
      ->getPath('vactory_starter_kit');
    $profile = new FileStorage($this->root . '/' . $profile_path . '/config/optional');
    $this->assertSame([], array_diff($existing, $profile->listAll()));
    $config_manager = $this->container->get('config.manager');
    foreach ($existing as $name) {
      // Deleting a media type also deletes its fields and displays.
      $config_manager->loadConfigEntityByName($name)?->delete();
    }
  }

}
