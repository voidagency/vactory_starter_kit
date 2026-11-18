<?php

namespace Drupal\Tests\vactory_toolbox\ExistingSite;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * The module can be enabled on a site built from the profile.
 *
 * @group vactory_toolbox
 */
class ModuleInstallTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = ['vactory_toolbox'];

  /**
   * The module and its form display are installed.
   */
  public function testInstall(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('vactory_toolbox'));
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('block_content.vactory_toolbox.default');
    $this->assertNotNull($display);
    $this->assertNotContains('workbench_moderation', $display->getDependencies()['module'] ?? []);
  }

}
