<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;

/**
 * External assets declared in the profile's *.libraries.yml files.
 *
 * @group vactory_core
 */
class ExternalLibrariesTest extends UnitTestCase {

  /**
   * Hosts that no longer serve files.
   */
  const DEAD_HOSTS = ['rawgit.com', 'cdn.rawgit.com'];

  /**
   * Returns every external JS/CSS URL, keyed by "file: library".
   */
  protected function externalAssets(): array {
    $root = dirname(__DIR__, 5);
    $directory = new \RecursiveDirectoryIterator($root . '/modules', \FilesystemIterator::SKIP_DOTS);
    $assets = [];
    foreach (new \RecursiveIteratorIterator($directory) as $file) {
      $path = $file->getPathname();
      if (!str_ends_with($path, '.libraries.yml') || str_contains($path, '/node_modules/')) {
        continue;
      }
      $libraries = Yaml::decode(file_get_contents($path)) ?? [];
      foreach ($libraries as $name => $library) {
        if (!is_array($library)) {
          continue;
        }
        $files = is_array($library['js'] ?? NULL) ? array_keys($library['js']) : [];
        foreach ((array) ($library['css'] ?? []) as $group) {
          $files = array_merge($files, is_array($group) ? array_keys($group) : []);
        }
        foreach ($files as $url) {
          if (preg_match('#^(https?:)?//#', $url)) {
            $label = str_replace($root . '/', '', $path) . ': ' . $name;
            $assets[] = [$label, $url];
          }
        }
      }
    }
    return $assets;
  }

  /**
   * No library loads a file from a CDN that is shut down.
   */
  public function testNoDeadCdn(): void {
    $dead = [];
    foreach ($this->externalAssets() as [$library, $url]) {
      $host = parse_url(str_starts_with($url, '//') ? 'https:' . $url : $url, PHP_URL_HOST);
      if (in_array($host, self::DEAD_HOSTS, TRUE)) {
        $dead[] = "$library ($url)";
      }
    }
    $this->assertSame([], $dead);
  }

}
