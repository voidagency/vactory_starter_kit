<?php

namespace Drupal\Tests\vactory_admin_hardening\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the real routes of the config, Features and migrate_tools UIs.
 *
 * @group vactory_admin_hardening
 */
class SensitiveAdminRouteAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'config',
    'config_update',
    'features',
    'features_ui',
    'migrate',
    'migrate_plus',
    'migrate_tools',
    'vactory_admin_hardening',
  ];

  /**
   * The super user (uid 1).
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $admin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['system', 'user']);
    // Anonymous first so the super user gets uid 1.
    User::create(['uid' => 0, 'name' => ''])->save();
    $this->admin = User::create(['uid' => 1, 'name' => 'admin', 'status' => 1]);
    $this->admin->save();
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Checks a route for the super user.
   */
  protected function allowed(string $route, array $parameters = []): bool {
    return $this->container->get('access_manager')
      ->checkNamedRoute($route, $parameters, $this->admin);
  }

  /**
   * The core configuration UI is denied even to uid 1.
   */
  public function testConfigUiDenied(): void {
    $routes = [
      'config.sync' => [],
      'config.import_full' => [],
      'config.import_single' => [],
      'config.export_full' => [],
      'config.export_download' => [],
      'config.export_single' => [
        'config_type' => 'system.simple',
        'config_name' => 'system.site',
      ],
    ];
    foreach ($routes as $route => $parameters) {
      $this->assertFalse($this->allowed($route, $parameters), "$route is denied to uid 1");
    }
  }

  /**
   * The migrate_tools UI is denied even to uid 1.
   */
  public function testMigrateToolsUiDenied(): void {
    $this->assertFalse($this->allowed('entity.migration_group.list'), 'entity.migration_group.list is denied to uid 1');
    $this->assertFalse($this->allowed('entity.migration_group.add_form'), 'entity.migration_group.add_form is denied to uid 1');
  }

  /**
   * Features and other admin pages stay reachable.
   */
  public function testOtherAdminRoutesAllowed(): void {
    $this->assertTrue($this->allowed('system.admin_config'), 'system.admin_config is allowed');
    $this->assertTrue($this->allowed('system.admin_config_development'), 'system.admin_config_development is allowed');
    $this->assertTrue($this->allowed('features.export'), 'features.export is allowed');
  }

  /**
   * Sanity check: without the module, uid 1 reaches the config UI.
   */
  public function testConfigUiAllowedWithoutModule(): void {
    $this->container->get('module_installer')->uninstall(['vactory_admin_hardening']);
    $this->admin = User::load(1);
    $this->assertTrue($this->allowed('config.sync'), 'config.sync is allowed without the module');
    $this->assertTrue($this->allowed('config.import_single'), 'config.import_single is allowed without the module');
  }

}
