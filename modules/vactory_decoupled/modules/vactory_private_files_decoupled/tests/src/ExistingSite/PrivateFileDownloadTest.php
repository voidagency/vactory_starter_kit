<?php

namespace Drupal\Tests\vactory_private_files_decoupled\ExistingSite;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file_entity\Entity\FileEntity;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Private file downloads for the decoupled front, built on drupal/file_entity.
 *
 * Our hook_file_access() relies on file_entity's downloadUrl() and on its
 * file_entity.file_download route. file_entity is a patched dependency whose
 * fix once caused an urgent regression (#53706): run this test on a
 * candidate version before upgrading it.
 *
 * @group vactory_private_files_decoupled
 * @group dependencies
 */
class PrivateFileDownloadTest extends ExistingSiteBase {

  /**
   * Content of the test file.
   */
  const CONTENT = 'private test content';

  /**
   * Private file used by the test.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $directory = 'private://vactory-private-test';
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $uri = $directory . '/' . strtolower($this->randomMachineName(8)) . '.txt';
    file_put_contents($uri, self::CONTENT);
    $this->file = File::create([
      'uri' => $uri,
      'filename' => basename($uri),
      'status' => 1,
      'uid' => 1,
    ]);
    $this->file->save();
    $this->markEntityForCleanup($this->file);
  }

  /**
   * Fetches a URL anonymously.
   */
  protected function fetch(string $url) {
    if (!str_starts_with($url, 'http')) {
      $url = $this->baseUrl . $url;
    }
    return \Drupal::httpClient()->get($url, [
      'http_errors' => FALSE,
      'allow_redirects' => FALSE,
    ]);
  }

  /**
   * File entities are file_entity's class (downloadUrl() is used by us).
   */
  public function testFileEntityClass(): void {
    $this->assertInstanceOf(FileEntity::class, $this->file);
    $this->assertSame('file_entity.file_download', $this->file->downloadUrl()->getRouteName());
  }

  /**
   * A tokenized URL downloads the private file, a bare one is refused.
   */
  public function testTokenizedDownload(): void {
    $urls = \Drupal::service('vactory_private_files_decoupled')->generatePrivateFileUrl($this->file);
    $this->assertStringContainsString('sessionId=', $urls['_default']);

    $response = $this->fetch($urls['_default']);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(self::CONTENT, (string) $response->getBody());

    $bare = $this->fetch($this->file->downloadUrl()->toString());
    $this->assertSame(403, $bare->getStatusCode());

    $tampered = $this->fetch(preg_replace('/sessionId=[^&]+/', 'sessionId=invalid', $urls['_default']));
    $this->assertSame(403, $tampered->getStatusCode());
  }

}
