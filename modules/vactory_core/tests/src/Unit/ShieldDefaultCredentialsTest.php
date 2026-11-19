<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;

/**
 * The profile does not ship Basic Auth credentials for Shield.
 *
 * Shipped credentials are public: a site enabling Shield without changing
 * them would be protected by a known password.
 *
 * @group vactory_core
 */
class ShieldDefaultCredentialsTest extends UnitTestCase {

  /**
   * The install configuration has empty Shield credentials.
   */
  public function testNoDefaultCredentials(): void {
    $file = dirname(__DIR__, 5) . '/config/install/shield.settings.yml';
    $this->assertFileExists($file);
    $config = Yaml::decode(file_get_contents($file));
    $this->assertFalse($config['shield_enable']);
    $this->assertSame('', $config['credentials']['shield']['user'] ?? '');
    $this->assertSame('', $config['credentials']['shield']['pass'] ?? '');
  }

}
