<?php

namespace Drupal\Tests\vactory_dynamic_field\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\vactory_dynamic_field\Traits\DeclaredDependenciesTrait;

/**
 * Vactory_dynamic_field installs with its declared dependencies only.
 *
 * The block type vactory_block_component needs block_content; its
 * translation settings need content_translation. Without them Drupal refused
 * "drush en vactory_dynamic_field" (UnmetDependenciesException, #71511).
 *
 * @group vactory_dynamic_field
 * @group dependencies
 */
class DynamicFieldInstallTest extends KernelTestBase {

  use DeclaredDependenciesTrait;

  /**
   * The block type of the dynamic blocks.
   */
  const BLOCK_TYPE = 'block_content.type.vactory_block_component';

  /**
   * Translation settings of the block type, for content_translation.
   */
  const TRANSLATION_SETTINGS = 'language.content_settings.block_content.vactory_block_component';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * The shipped config has options of modules this test does not check.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpProfileSite();
  }

  /**
   * Config/install needs only modules vactory_dynamic_field requires.
   */
  public function testShippedConfigNeedsDeclaredModules(): void {
    $unmet = $this->configNeedingUndeclaredModules('vactory_dynamic_field', 'install');
    $this->assertSame([], $unmet);
    // The translation settings wait for content_translation (and language).
    $waiting = $this->configNeedingUndeclaredModules('vactory_dynamic_field', 'optional');
    $this->assertSame([
      self::TRANSLATION_SETTINGS => ['content_translation', 'language'],
    ], $waiting);
  }

  /**
   * Without content_translation, the module installs without translation.
   */
  public function testInstallWithDeclaredDependencies(): void {
    $this->installWithDependencies(['vactory_dynamic_field']);

    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('vactory_dynamic_field'));
    $this->assertTrue($module_handler->moduleExists('block_content'));
    $this->assertFalse($module_handler->moduleExists('content_translation'));
    $installed = $this->shippedConfig('vactory_dynamic_field', 'install');
    $this->assertContains(self::BLOCK_TYPE, $installed);
    foreach ($installed as $name) {
      $this->assertConfigExists($name);
    }
    $this->assertConfigExists(self::TRANSLATION_SETTINGS, FALSE);
  }

  /**
   * Content_translation installed later brings the translation settings.
   */
  public function testTranslationSettingsFollowContentTranslation(): void {
    $this->installWithDependencies(['vactory_dynamic_field']);
    $this->installWithDependencies(['content_translation']);
    $this->assertTranslationSettingsShipped();
  }

  /**
   * With content_translation already there, as in the profile, too.
   */
  public function testTranslationSettingsWithContentTranslationFirst(): void {
    $this->installWithDependencies(['content_translation']);
    $this->installWithDependencies(['vactory_dynamic_field']);
    $this->assertTranslationSettingsShipped();
  }

  /**
   * Asserts the shipped translation settings are installed.
   */
  protected function assertTranslationSettingsShipped(): void {
    $this->assertConfigExists(self::TRANSLATION_SETTINGS);
    $settings = $this->config(self::TRANSLATION_SETTINGS);
    $this->assertTrue($settings->get('third_party_settings.content_translation.enabled'));
    // Installed from the module's config, not created by content_translation.
    $this->assertNotEmpty($settings->get('_core.default_config_hash'));
  }

}
