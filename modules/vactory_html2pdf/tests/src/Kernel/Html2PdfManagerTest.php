<?php

namespace Drupal\Tests\vactory_html2pdf\Kernel;

use Drupal\KernelTests\KernelTestBase;

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
    // See tearDown(): never write in the real working directory.
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
    // Until #71157 is fixed the PDF lands in "public/" relative to the
    // current directory, with odd permissions: it was written in a temporary
    // working directory, removed here.
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

}
