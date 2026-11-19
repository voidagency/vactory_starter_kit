<?php

namespace Drupal\Tests\vactory_form_autosave\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * The module and its default configuration can be installed.
 *
 * @group vactory_form_autosave
 */
class ModuleInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The module lives in the profile: its config schema is only discovered
    // when the profile is the install profile.
    $this->setInstallProfile('vactory_starter_kit');
  }

  /**
   * Installs the module like "drush en" does.
   */
  public function testInstall(): void {
    $this->container->get('module_installer')->install(['vactory_form_autosave']);
    $this->assertTrue($this->container->get('module_handler')->moduleExists('vactory_form_autosave'));
    $config = $this->config('vactory_form_autosave.settings');
    $this->assertSame('3', $config->get('lifetime'));
    $this->assertSame(['vactory_form_autosave'], $config->get('dependencies.enforced.module'));
  }

}
