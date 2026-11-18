<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Patches declared in the profile composer.json target real packages.
 *
 * Composer-patches matches the package name exactly: a patch declared under
 * a misspelled name is silently never applied.
 *
 * @group vactory_core
 */
class ComposerPatchesTest extends UnitTestCase {

  /**
   * Every patched package is required by the profile (directly or not).
   */
  public function testPatchedPackagesAreRequired(): void {
    $root = dirname(__DIR__, 5);
    $this->assertFileExists($root . '/composer.json');
    $composer = json_decode(file_get_contents($root . '/composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
    $required = array_keys(($composer['require'] ?? []) + ($composer['require-dev'] ?? []));
    // Patches may also target transitive dependencies.
    if (file_exists($root . '/composer.lock')) {
      $lock = json_decode(file_get_contents($root . '/composer.lock'), TRUE, 512, JSON_THROW_ON_ERROR);
      foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
        $required[] = $package['name'];
      }
    }
    $patched = array_keys($composer['extra']['patches'] ?? []);
    $this->assertNotEmpty($patched);
    $this->assertSame([], array_values(array_diff($patched, $required)), 'Patches declared for packages that are not required.');
  }

}
