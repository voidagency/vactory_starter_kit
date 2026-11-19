<?php

namespace Drupal\Tests\vactory_admin_hardening\Unit;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_admin_hardening\Routing\SensitiveAdminRouteSubscriber;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests which routes the sensitive admin route subscriber denies.
 *
 * @coversDefaultClass \Drupal\vactory_admin_hardening\Routing\SensitiveAdminRouteSubscriber
 * @group vactory_admin_hardening
 */
class SensitiveAdminRouteSubscriberTest extends UnitTestCase {

  /**
   * Requirement set on the untouched fixture routes.
   */
  const PERMISSION = 'administer site configuration';

  /**
   * Builds a collection, runs the subscriber on it and returns it.
   *
   * @param array $routes
   *   Paths keyed by route name.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The altered collection.
   */
  protected function alter(array $routes): RouteCollection {
    $collection = new RouteCollection();
    foreach ($routes as $name => $path) {
      $collection->add($name, new Route($path, [], ['_permission' => self::PERMISSION]));
    }
    (new SensitiveAdminRouteSubscriber())->onAlterRoutes(new RouteBuildEvent($collection));
    return $collection;
  }

  /**
   * Asserts a route is denied to everyone.
   */
  protected function assertDenied(RouteCollection $collection, string $name): void {
    $this->assertSame('FALSE', $collection->get($name)->getRequirement('_access'), "$name is denied");
  }

  /**
   * Asserts a route keeps its requirements untouched.
   */
  protected function assertUntouched(RouteCollection $collection, string $name): void {
    $route = $collection->get($name);
    $this->assertNull($route->getRequirement('_access'), "$name is not denied");
    $this->assertSame(['_permission' => self::PERMISSION], $route->getRequirements(), "$name keeps its requirements");
  }

  /**
   * Paths under the denied prefixes: exact prefix and sub-paths.
   */
  public static function deniedPathProvider(): array {
    return [
      'migrate exact' => ['/admin/structure/migrate'],
      'migrate sub' => ['/admin/structure/migrate/manage/{migration_group}/migrations'],
      'migration exact' => ['/admin/structure/migration'],
      'migration sub' => ['/admin/structure/migration/{migration}/edit'],
      'migrate-plugin exact' => ['/admin/config/system/migrate-plugin'],
      'migrate-plugin sub' => ['/admin/config/system/migrate-plugin/foo'],
      'migration-messages exact' => ['/admin/reports/migration-messages'],
      'migration-messages sub' => ['/admin/reports/migration-messages/{migration_id}'],
      'configuration exact' => ['/admin/config/development/configuration'],
      'configuration sync' => ['/admin/config/development/configuration/full/import'],
      'configuration single import' => ['/admin/config/development/configuration/single/import'],
    ];
  }

  /**
   * Near-misses that share a prefix string but not a path segment.
   */
  public static function allowedPathProvider(): array {
    return [
      'migrations-foo' => ['/admin/structure/migrations-foo'],
      'migrate-foo' => ['/admin/structure/migrate-foo'],
      'migrationx' => ['/admin/structure/migrationx/list'],
      'migrate-plugins' => ['/admin/config/system/migrate-plugins'],
      'migration-messages-x' => ['/admin/reports/migration-messages-x'],
      'configurationx' => ['/admin/config/development/configurationx'],
      'configurationx sub' => ['/admin/config/development/configurationx/foo'],
      'features' => ['/admin/config/development/features'],
      'features sub' => ['/admin/config/development/features/edit/{featurename}'],
      'backup_migrate' => ['/admin/config/development/backup_migrate'],
      'backup_migrate sub' => ['/admin/config/development/backup_migrate/settings'],
      'structure root' => ['/admin/structure'],
      'development root' => ['/admin/config/development'],
      'prefix not at start' => ['/fr/admin/structure/migrate'],
    ];
  }

  /**
   * @covers ::alterRoutes
   * @dataProvider deniedPathProvider
   */
  public function testDeniedPath(string $path): void {
    $collection = $this->alter(['fixture.route' => $path]);
    $this->assertDenied($collection, 'fixture.route');
  }

  /**
   * @covers ::alterRoutes
   * @dataProvider allowedPathProvider
   */
  public function testAllowedPath(string $path): void {
    $collection = $this->alter(['fixture.route' => $path]);
    $this->assertUntouched($collection, 'fixture.route');
  }

  /**
   * Migration JSON:API resources are denied, other resources are not.
   *
   * @covers ::alterRoutes
   */
  public function testJsonApiRouteNames(): void {
    $collection = $this->alter([
      'jsonapi.migration--migration.collection' => '/jsonapi/migration/migration',
      'jsonapi.migration--migration.individual' => '/jsonapi/migration/migration/{entity}',
      'jsonapi.migration_group--migration_group.collection' => '/jsonapi/migration_group/migration_group',
      'jsonapi.node--page.collection' => '/jsonapi/node/page',
      'jsonapi.user--user.individual' => '/jsonapi/user/user/{entity}',
      'jsonapi.migrations--foo.collection' => '/jsonapi/migrations/foo',
      'jsonapi.resource_list' => '/jsonapi',
      'other.migration--x' => '/other',
    ]);
    $this->assertDenied($collection, 'jsonapi.migration--migration.collection');
    $this->assertDenied($collection, 'jsonapi.migration--migration.individual');
    $this->assertDenied($collection, 'jsonapi.migration_group--migration_group.collection');
    $this->assertUntouched($collection, 'jsonapi.node--page.collection');
    $this->assertUntouched($collection, 'jsonapi.user--user.individual');
    $this->assertUntouched($collection, 'jsonapi.migrations--foo.collection');
    $this->assertUntouched($collection, 'jsonapi.resource_list');
    $this->assertUntouched($collection, 'other.migration--x');
  }

  /**
   * Denying replaces nothing else and leaves the rest of the collection alone.
   *
   * @covers ::alterRoutes
   */
  public function testMixedCollection(): void {
    $collection = $this->alter([
      'config.sync' => '/admin/config/development/configuration',
      'features.admin' => '/admin/config/development/features',
      'system.admin' => '/admin',
    ]);
    $this->assertCount(3, $collection);
    $this->assertDenied($collection, 'config.sync');
    // The original requirement stays: "_access: FALSE" is ANDed with it.
    $this->assertSame(self::PERMISSION, $collection->get('config.sync')->getRequirement('_permission'));
    $this->assertUntouched($collection, 'features.admin');
    $this->assertUntouched($collection, 'system.admin');
  }

}
