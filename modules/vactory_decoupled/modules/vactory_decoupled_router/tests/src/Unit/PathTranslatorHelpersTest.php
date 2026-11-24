<?php

namespace Drupal\Tests\vactory_decoupled_router\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\vactory_decoupled_router\Controller\PathTranslator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Characterization of the path helpers of PathTranslator.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled_router\Controller\PathTranslator
 * @group vactory_decoupled_router
 */
class PathTranslatorHelpersTest extends UnitTestCase {

  /**
   * Calls a non public method of a controller built without its services.
   *
   * @param string $method
   *   The method name.
   * @param array $arguments
   *   The arguments.
   */
  protected function callHelper(string $method, array $arguments) {
    $class = new \ReflectionClass(PathTranslator::class);
    $controller = $class->newInstanceWithoutConstructor();
    $reflection = $class->getMethod($method);
    $reflection->setAccessible(TRUE);
    return $reflection->invokeArgs($controller, $arguments);
  }

  /**
   * Which paths are seen as an internal node path (redirected to the alias).
   *
   * @covers ::isFollowNodePathPattern
   * @dataProvider providerNodePaths
   */
  public function testNodePathPattern(string $path, bool $expected): void {
    $this->assertSame($expected, (bool) $this->callHelper('isFollowNodePathPattern', [$path]));
  }

  /**
   * Data provider for testNodePathPattern().
   */
  public static function providerNodePaths(): array {
    return [
      'node path' => ['/node/12', TRUE],
      'node path with language prefix' => ['/fr/node/12', TRUE],
      'any path ending with a node path' => ['/news/node/12', TRUE],
      'absolute URL' => ['https://example.com/en/node/12', TRUE],
      'no leading slash' => ['node/12', FALSE],
      'trailing slash' => ['/node/12/', FALSE],
      'upper case' => ['/NODE/12', FALSE],
      'query string' => ['/node/12?page=2', FALSE],
      'edit form' => ['/node/12/edit', FALSE],
      'not a number' => ['/node/add', FALSE],
      'alias' => ['/about-us', FALSE],
      'alias containing node' => ['/node-12', FALSE],
    ];
  }

  /**
   * The base path of the site is removed from the start of the path only.
   *
   * @covers ::cleanSubdirInPath
   * @dataProvider providerSubdirPaths
   */
  public function testCleanSubdirInPath(string $base_path, string $path, string $expected): void {
    $script = $base_path . '/index.php';
    $request = Request::create('http://example.com' . $script . '/api/router');
    $request->server->set('SCRIPT_NAME', $script);
    $request->server->set('SCRIPT_FILENAME', '/var/www' . $script);
    $this->assertSame($base_path, $request->getBasePath());
    $arguments = [$path, $request];
    $this->assertSame($expected, $this->callHelper('cleanSubdirInPath', $arguments));
  }

  /**
   * Data provider for testCleanSubdirInPath().
   */
  public static function providerSubdirPaths(): array {
    return [
      'no base path' => ['', '/about-us', '/about-us'],
      'base path removed' => ['/d8', '/d8/about-us', '/about-us'],
      'base path inside the path' => ['/d8', '/news/d8', '/news/d8'],
      'path without the base path' => ['/d8', '/about-us', '/about-us'],
      'base path with a dot' => ['/v1.2', '/v1.2/about', '/about'],
      'dot of the base path is literal' => ['/v1.2', '/v1x2/a', '/v1x2/a'],
      'alias starting like the base path' => ['/d8', '/d8-news', '-news'],
    ];
  }

  /**
   * The entity type of a route is the one of its first entity parameter.
   *
   * @covers ::findEntityTypeFromRoute
   * @dataProvider providerRouteParameters
   */
  public function testFindEntityTypeFromRoute(?array $parameters, ?string $expected): void {
    $route = new Route('/path');
    if ($parameters !== NULL) {
      $route->setOption('parameters', $parameters);
    }
    $this->assertSame($expected, $this->callHelper('findEntityTypeFromRoute', [$route]));
  }

  /**
   * Data provider for testFindEntityTypeFromRoute().
   */
  public static function providerRouteParameters(): array {
    $node = ['type' => 'entity:node'];
    $user = ['type' => 'entity:user'];
    return [
      'no parameters option' => [NULL, NULL],
      'no parameter' => [[], NULL],
      'one entity' => [['node' => $node], 'node'],
      'first entity wins' => [['user' => $user, 'node' => $node], 'user'],
      'parameter without type' => [['foo' => [], 'node' => $node], 'node'],
      'not an entity' => [['langcode' => ['type' => 'language']], NULL],
      'entity without type id' => [['foo' => ['type' => 'entity:']], NULL],
      'revision' => [['r' => ['type' => 'entity_revision:node']], NULL],
    ];
  }

}
