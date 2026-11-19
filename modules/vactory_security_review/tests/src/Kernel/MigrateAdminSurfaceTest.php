<?php

namespace Drupal\Tests\vactory_security_review\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\security_review\CheckResult;
use Drupal\user\Entity\Role;
use Drupal\vactory_security_review\Checks\MigrateAdminSurface;

/**
 * Tests the "Migrate administration surface" security review check.
 *
 * @coversDefaultClass \Drupal\vactory_security_review\Checks\MigrateAdminSurface
 * @group vactory_security_review
 */
class MigrateAdminSurfaceTest extends KernelTestBase {

  /**
   * Finding reported when the hardening module is missing.
   */
  const MODULE_FINDING = 'The "vactory_admin_hardening" module is not installed: the migrate administration UI is reachable over HTTP. Migrations should be run through Drush only.';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'config',
    'migrate',
    'migrate_tools',
    'security_review',
    'vactory_security_review',
    'vactory_admin_hardening',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['system', 'user', 'security_review']);
  }

  /**
   * Runs the check.
   */
  protected function runCheck(): CheckResult {
    return (new MigrateAdminSurface())->run();
  }

  /**
   * Uninstalls the hardening module.
   *
   * The module list is rescanned afterwards and no longer sees the profile
   * modules, so roles must be saved before this is called.
   */
  protected function uninstallHardening(): void {
    $this->container->get('module_installer')->uninstall(['vactory_admin_hardening']);
  }

  /**
   * Creates a role holding the given permissions.
   */
  protected function createRoleWith(string $id, array $permissions, bool $is_admin = FALSE): void {
    $role = Role::create(['id' => $id, 'label' => $id, 'is_admin' => $is_admin]);
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();
  }

  /**
   * Hardening module installed, no dangerous permission: success.
   *
   * @covers ::run
   */
  public function testSuccess(): void {
    $this->createRoleWith('editor', [
      'access content overview',
      'administer site configuration',
    ]);
    $result = $this->runCheck();
    $this->assertSame(CheckResult::SUCCESS, $result->result());
    $this->assertSame([], $result->findings());
  }

  /**
   * Hardening module missing: fail with the module finding only.
   *
   * @covers ::run
   */
  public function testModuleMissing(): void {
    $this->uninstallHardening();
    $result = $this->runCheck();
    $this->assertSame(CheckResult::FAIL, $result->result());
    $this->assertSame([self::MODULE_FINDING], $result->findings());
  }

  /**
   * Each dangerous permission on a non-admin role is reported.
   *
   * @covers ::run
   */
  public function testDangerousPermissionsOnNonAdminRole(): void {
    $this->createRoleWith('importer', ['import configuration']);
    $this->createRoleWith('migrator', ['administer migrations']);
    $this->createRoleWith('both', [
      'import configuration',
      'administer migrations',
    ]);
    $result = $this->runCheck();
    $this->assertSame(CheckResult::FAIL, $result->result());
    $expected = [
      'Role "both" holds the "import configuration" permission.',
      'Role "both" holds the "administer migrations" permission.',
      'Role "importer" holds the "import configuration" permission.',
      'Role "migrator" holds the "administer migrations" permission.',
    ];
    $findings = $result->findings();
    sort($expected);
    sort($findings);
    $this->assertSame($expected, $findings);
  }

  /**
   * Admin roles are expected to hold the permissions and are ignored.
   *
   * @covers ::run
   */
  public function testAdminRoleIgnored(): void {
    $this->createRoleWith('administrator', [
      'import configuration',
      'administer migrations',
    ], TRUE);
    $this->assertSame(CheckResult::SUCCESS, $this->runCheck()->result());
  }

  /**
   * Both kinds of findings are reported together.
   *
   * @covers ::run
   */
  public function testModuleMissingAndDangerousPermission(): void {
    $this->createRoleWith('importer', ['import configuration']);
    $this->uninstallHardening();
    $result = $this->runCheck();
    $this->assertSame(CheckResult::FAIL, $result->result());
    $this->assertSame([
      self::MODULE_FINDING,
      'Role "importer" holds the "import configuration" permission.',
    ], $result->findings());
  }

}
