<?php

namespace Drupal\Tests\vactory_decoupled\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\Dependency;
use Drupal\Tests\UnitTestCase;

/**
 * Modules declare the contrib modules whose PHP code they use.
 *
 * A module that uses "Drupal\<module>\..." classes without declaring
 * <module> can be enabled without it and then fails at runtime.
 *
 * @group vactory_decoupled
 */
class ModuleDependenciesTest extends UnitTestCase {

  /**
   * Modules provided by Drupal core, never required in .info.yml here.
   */
  const CORE = [
    'system', 'user', 'node', 'field', 'file', 'image', 'taxonomy', 'block',
    'block_content', 'menu_link_content', 'path_alias', 'language',
    'serialization', 'jsonapi', 'rest', 'views', 'media', 'text', 'filter',
    'link', 'options', 'datetime', 'locale', 'content_translation',
  ];

  /**
   * Checks that every used module namespace is a declared dependency.
   *
   * @dataProvider providerModules
   */
  public function testUsedModulesAreDeclared(string $path): void {
    $info_file = glob($path . '/*.info.yml')[0];
    $module = basename($info_file, '.info.yml');
    $info = Yaml::decode(file_get_contents($info_file));
    $declared = array_map(
      fn ($dependency) => Dependency::createFromString($dependency)->getName(),
      $info['dependencies'] ?? []
    );

    $used = [];
    $directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
    $files = new \RecursiveIteratorIterator($directory);
    foreach ($files as $file) {
      if (!preg_match('/\.(php|module|inc|install)$/', $file->getFilename()) || str_contains($file->getPathname(), '/tests/')) {
        continue;
      }
      preg_match_all('/^use Drupal\\\\([a-z0-9_]+)\\\\/m', file_get_contents($file->getPathname()), $matches);
      $used = array_merge($used, $matches[1]);
    }
    $ignored = [$module, 'Core', 'Component'];
    $undeclared = array_diff(array_unique($used), $declared, self::CORE, $ignored);
    // Modules reached through a declared Vactory module are accepted.
    $undeclared = array_filter($undeclared, fn ($name) => !str_starts_with($name, 'vactory_'));
    $this->assertSame([], array_values($undeclared), "$module uses modules it does not declare.");
  }

  /**
   * Data provider for testUsedModulesAreDeclared().
   */
  public static function providerModules(): array {
    $root = dirname(__DIR__, 3) . '/modules';
    return [
      'vactory_decoupled_flag' => [$root . '/vactory_decoupled_flag'],
      'vactory_decoupled_search_ai' => [$root . '/vactory_decoupled_search/modules/vactory_decoupled_search_ai'],
    ];
  }

}
