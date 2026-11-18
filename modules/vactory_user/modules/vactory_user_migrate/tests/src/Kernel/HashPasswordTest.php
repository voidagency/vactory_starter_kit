<?php

namespace Drupal\Tests\vactory_user_migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\vactory_user_migrate\Plugin\migrate\process\HashPassword;

/**
 * Tests the hash_password process plugin with the core password service.
 *
 * @coversDefaultClass \Drupal\vactory_user_migrate\Plugin\migrate\process\HashPassword
 * @group vactory_user_migrate
 */
class HashPasswordTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'migrate',
    'vactory_user_migrate',
  ];

  /**
   * The plugin hashes the value with the site password service.
   *
   * @covers ::create
   * @covers ::transform
   */
  public function testTransform(): void {
    $plugin = HashPassword::create($this->container, [], 'hash_password', []);
    $hash = $plugin->transform('S3cret!pass', $this->createMock(MigrateExecutableInterface::class), new Row(), 'pass');
    $this->assertNotSame('S3cret!pass', $hash);
    $this->assertTrue($this->container->get('password')->check('S3cret!pass', $hash));
  }

}
