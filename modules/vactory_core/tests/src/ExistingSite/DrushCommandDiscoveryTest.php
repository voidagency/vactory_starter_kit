<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Checks that Drush finds the commands of the profile (Redmine #71531).
 *
 * Runs the real "drush list" of the site, the way an operator or a cron
 * does: a command declared where Drush does not look is "not defined".
 *
 * @group vactory_core
 */
class DrushCommandDiscoveryTest extends VactoryExistingSiteBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = [
    'vactory_mailchimp_newsletter',
    'vactory_cloudinary',
  ];

  /**
   * The commands of the modules are listed by Drush.
   */
  public function testCommandsAreListed(): void {
    $this->drush('list', [], ['format' => 'json']);
    $names = [];
    foreach ($this->getOutputFromJSON()['commands'] ?? [] as $command) {
      $names[] = $command['name'];
    }
    $this->assertContains('send_newsletters', $names);
    $this->assertContains('move-to-cloudinary', $names);
    // The alias resolves too.
    $this->drush('help', ['mtc']);
    $this->assertStringContainsString('cloudinary', $this->getOutput());
  }

}
