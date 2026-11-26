<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * The profile ships no secret.
 *
 * The repository is public and its configuration is installed as is on new
 * sites: a secret written there is exposed and may end up in production.
 * Keys are entered in the back office or read from settings.php.
 *
 * @group vactory_core
 */
class NoSecretsInProfileTest extends UnitTestCase {

  /**
   * Secret formats searched, by label.
   */
  private const PATTERNS = [
    'Mailchimp API key' => '/\b[0-9a-f]{32}-us\d{1,2}\b/',
    'private key' => '/-----BEGIN (?:RSA |EC |DSA |OPENSSH |ENCRYPTED )?PRIVATE KEY-----/',
  ];

  /**
   * Extensions of the files searched.
   */
  private const EXTENSIONS = [
    'yml', 'yaml', 'php', 'module', 'install', 'inc', 'theme', 'profile',
    'js', 'json', 'twig', 'env', 'key', 'pem', 'txt', 'md',
  ];

  /**
   * Directories not searched.
   */
  private const SKIPPED_DIRECTORIES = ['.git', 'vendor', 'node_modules'];

  /**
   * No file of the profile holds a secret.
   */
  public function testNoSecret(): void {
    $found = [];
    $root = dirname(__DIR__, 5);
    foreach ($this->files($root) as $path) {
      $contents = file_get_contents($path);
      foreach (self::PATTERNS as $label => $pattern) {
        if (preg_match($pattern, $contents)) {
          // The secret itself is never printed.
          $found[] = $label . ': ' . substr($path, strlen($root) + 1);
        }
      }
    }
    $this->assertSame([], $found, 'Secrets found in the profile; remove them and revoke them.');
  }

  /**
   * Lists the files searched under the given directory.
   */
  private function files(string $root): \Generator {
    $directories = new \RecursiveCallbackFilterIterator(
      new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
      static fn(\SplFileInfo $file): bool => !$file->isDir() || !in_array($file->getFilename(), self::SKIPPED_DIRECTORIES, TRUE),
    );
    foreach (new \RecursiveIteratorIterator($directories) as $file) {
      if ($file->isFile() && in_array(strtolower($file->getExtension()), self::EXTENSIONS, TRUE)) {
        yield $file->getPathname();
      }
    }
  }

}
