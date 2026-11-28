<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Checks how the modules of the profile declare their Drush commands.
 *
 * Drush 12 and 13 no longer read the drush.command tag of a service in a
 * module *.services.yml file: such a command is "not defined". Commands are
 * declared in src/Drush/Commands (static create()).
 *
 * @group vactory_core
 */
class DrushCommandDeclarationTest extends UnitTestCase {

  /**
   * No module services file tags a service as a Drush command.
   */
  public function testNoDrushCommandTagInModuleServices(): void {
    $profile = dirname(__DIR__, 5);
    $found = [];
    $files = new \RegexIterator(
      new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($profile . '/modules', \FilesystemIterator::SKIP_DOTS)),
      '/\.services\.yml$/'
    );
    foreach ($files as $file) {
      $path = $file->getPathname();
      if (basename($path) === 'drush.services.yml') {
        continue;
      }
      if (preg_match('/name:\s*drush\.command\b/', file_get_contents($path))) {
        $found[] = substr($path, strlen($profile) + 1);
      }
    }
    $this->assertSame([], $found, 'Declare Drush commands in src/Drush/Commands, not with the drush.command tag.');
  }

}
