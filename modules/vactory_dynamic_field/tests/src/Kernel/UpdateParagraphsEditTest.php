<?php

namespace Drupal\Tests\vactory_dynamic_field\Kernel;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Update hooks of vactory_dynamic_field enable paragraphs_edit when missing.
 *
 * @group vactory_dynamic_field
 */
class UpdateParagraphsEditTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * Module installer mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $installer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 3) . '/vactory_dynamic_field.install';
    $this->installer = $this->createMock(ModuleInstallerInterface::class);
    $this->container->set('module_installer', $this->installer);
  }

  /**
   * Update 9501 enables paragraphs_edit when it is not installed.
   */
  public function testUpdate9501InstallsMissingModule(): void {
    $this->installer->expects($this->once())->method('install')->with(['paragraphs_edit']);
    $this->assertTrue(function_exists('vactory_dynamic_field_update_9501'), 'Update hook 9501 exists.');
    vactory_dynamic_field_update_9501();
  }

  /**
   * Nothing is installed when paragraphs_edit is already enabled.
   */
  public function testUpdate9501WhenAlreadyEnabled(): void {
    $this->enableModules(['paragraphs_edit']);
    $this->installer->expects($this->never())->method('install');
    $this->assertNull(vactory_dynamic_field_update_9501());
  }

  /**
   * Update 9500 enables paragraphs_edit when it is not installed.
   */
  public function testUpdate9500InstallsMissingModule(): void {
    $this->installer->expects($this->once())->method('install')->with(['paragraphs_edit']);
    $sandbox = [];
    vactory_dynamic_field_update_9500($sandbox);
  }

}
