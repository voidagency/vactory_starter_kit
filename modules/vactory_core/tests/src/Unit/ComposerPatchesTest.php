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
   * Every patched package is required by the profile.
   */
  public function testPatchedPackagesAreRequired(): void {
    $file = dirname(__DIR__, 5) . '/composer.json';
    $composer = json_decode(file_get_contents($file), TRUE, 512, JSON_THROW_ON_ERROR);
    $required = array_keys(($composer['require'] ?? []) + ($composer['require-dev'] ?? []));
    $patched = array_keys($composer['extra']['patches'] ?? []);
    $this->assertNotEmpty($patched);
    $this->assertSame([], array_values(array_diff($patched, $required)), 'Patches declared for packages that are not required.');
  }

}
