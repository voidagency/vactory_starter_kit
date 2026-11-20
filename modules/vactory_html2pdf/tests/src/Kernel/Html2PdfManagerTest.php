<?php

namespace Drupal\Tests\vactory_html2pdf\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\vactory_html2pdf\Services\VactoryHtml2PdfException;

/**
 * Tests PDF generation with the default module settings.
 *
 * @coversDefaultClass \Drupal\vactory_html2pdf\Services\VactoryHtml2PdfManager
 * @group vactory_html2pdf
 */
class Html2PdfManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'file', 'vactory_html2pdf'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setInstallProfile('vactory_starter_kit');
    $this->installConfig(['vactory_html2pdf']);
    // Redmine #71157: the PDF used to land in "public/" relative to the
    // current directory. The tests run in a temporary working directory to
    // assert nothing is written there, and to never touch the real one.
    $this->previousCwd = getcwd();
    $dir = sys_get_temp_dir() . '/html2pdf-test-' . getmypid();
    mkdir($dir);
    chdir($dir);
  }

  /**
   * Working directory before the test.
   *
   * @var string
   */
  protected string $previousCwd;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $dir = getcwd();
    chdir($this->previousCwd);
    exec('chmod -R u+rwx ' . escapeshellarg($dir) . ' && rm -rf ' . escapeshellarg($dir));
    parent::tearDown();
  }

  /**
   * A PDF is generated although the settings form was never saved.
   *
   * @covers ::html2Pdf
   */
  public function testGenerateWithInstallConfig(): void {
    $this->assertNull($this->config('vactory_html2pdf.settings')->get('vactory_html2pdf_font_dirs'));
    $result = $this->container->get('vactory_html2pdf.manager')
      ->html2Pdf('<h1>Certificat</h1>', 'public://html2pdf-test/certificat.pdf');
    $this->assertFileExists($result['uri']);
    $this->assertStringStartsWith('%PDF', file_get_contents($result['uri']));
  }

  /**
   * The PDF is written at the requested stream URI (Redmine #71157).
   *
   * @covers ::html2Pdf
   */
  public function testOutputKeepsTheStreamUri(): void {
    $result = $this->container->get('vactory_html2pdf.manager')
      ->html2Pdf('<h1>Certificat</h1>', 'public://html2pdf-test/2026-11/certificat.pdf');

    $this->assertSame('public://html2pdf-test/2026-11/certificat.pdf', $result['uri']);
    $public = $this->container->get('file_system')->realpath('public://');
    $this->assertFileExists($public . '/html2pdf-test/2026-11/certificat.pdf');
    $this->assertStringStartsWith('%PDF', file_get_contents($result['uri']));
    // Nothing is written relative to the working directory.
    $this->assertDirectoryDoesNotExist(getcwd() . '/public');
    // The created directories can be read and traversed.
    $this->assertDirectoryIsReadable($public . '/html2pdf-test/2026-11');
    $this->assertSame(
      $this->container->get('file_url_generator')->generateAbsoluteString('public://html2pdf-test/2026-11/certificat.pdf'),
      $result['url']
    );
  }

  /**
   * A file name without parent directory lands at the root of the scheme.
   *
   * @covers ::html2Pdf
   */
  public function testOutputAtTheRootOfTheScheme(): void {
    $result = $this->container->get('vactory_html2pdf.manager')
      ->html2Pdf('<h1>Certificat</h1>', 'public://certificat.pdf');

    $this->assertSame('public://certificat.pdf', $result['uri']);
    $this->assertFileExists($this->container->get('file_system')->realpath('public://') . '/certificat.pdf');
  }

  /**
   * A scheme the site does not provide is reported, nothing is written.
   *
   * The private file path is not set in this test: "private://" is unknown.
   *
   * @covers ::html2Pdf
   */
  public function testUnavailableSchemeIsRejected(): void {
    try {
      $this->container->get('vactory_html2pdf.manager')
        ->html2Pdf('<h1>Certificat</h1>', 'private://html2pdf-test/certificat.pdf');
      $this->fail('An unavailable scheme must be rejected.');
    }
    catch (VactoryHtml2PdfException $e) {
      $this->assertStringContainsString('private', $e->getMessage());
    }
    $this->assertDirectoryDoesNotExist(getcwd() . '/private');
  }

}
