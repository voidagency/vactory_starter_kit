<?php

namespace Drupal\Tests\vactory_image_sitemap\ExistingSite;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Tests the /image-sitemap.xml route.
 *
 * @group vactory_image_sitemap
 */
class ImageSitemapXmlTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = ['vactory_image_sitemap'];

  /**
   * Generated sitemap file used by the test (default language).
   *
   * @var string
   */
  protected string $file;

  /**
   * Default language of the site.
   *
   * @var string
   */
  protected string $langcode;

  /**
   * Whether the test created the file.
   *
   * @var bool
   */
  protected bool $created = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->file = 'public://image_sitemap/' . $this->langcode . '_image_sitemap.xml';
    if (!file_exists($this->file)) {
      $dir = 'public://image_sitemap';
      \Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
      file_put_contents($this->file, '<?xml version="1.0" encoding="UTF-8"?><urlset/>');
      $this->created = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->created) {
      \Drupal::service('file_system')->delete($this->file);
    }
    parent::tearDown();
  }

  /**
   * The generated sitemap is served as XML.
   */
  public function testSitemapIsServed(): void {
    $url = $this->baseUrl . '/' . $this->langcode . '/image-sitemap.xml';
    $response = \Drupal::httpClient()->get($url, [
      'http_errors' => FALSE,
      'allow_redirects' => FALSE,
    ]);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertStringContainsString('xml', $response->getHeaderLine('Content-Type'));
    $this->assertStringContainsString('<urlset', (string) $response->getBody());
  }

}
