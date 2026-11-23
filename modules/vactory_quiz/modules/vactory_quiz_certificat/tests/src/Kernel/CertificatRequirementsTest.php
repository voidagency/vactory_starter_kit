<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the status report entry of the module (Redmine #71157).
 *
 * The private file path is not set in this test: "private://" is unknown.
 *
 * @group vactory_quiz_certificat
 */
class CertificatRequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'vactory_quiz_certificat',
  ];

  /**
   * The schema of the module settings does not describe every key.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Load the REQUIREMENT_* constants the way core does.
    include_once $this->root . '/core/includes/install.inc';
    $this->container->get('module_handler')
      ->loadInclude('vactory_quiz_certificat', 'install');
  }

  /**
   * Sets the generation method of the module settings.
   */
  protected function setMethod(string $method): void {
    $this->config('vactory_quiz_certificat.settings')
      ->set('method', $method)
      ->save();
  }

  /**
   * The html2pdf method without private file path is an error.
   */
  public function testMissingPrivatePathIsReported(): void {
    $this->setMethod('html2pdf');
    $requirements = vactory_quiz_certificat_requirements('runtime');

    $this->assertArrayHasKey('vactory_quiz_certificat_private_path', $requirements);
    $requirement = $requirements['vactory_quiz_certificat_private_path'];
    $this->assertSame(REQUIREMENT_ERROR, $requirement['severity']);
    $this->assertStringContainsString('file_private_path', (string) $requirement['description']);
    // Only the status report is concerned.
    $this->assertSame([], vactory_quiz_certificat_requirements('install'));
    $this->assertSame([], vactory_quiz_certificat_requirements('update'));
  }

  /**
   * The browser print method writes no file: nothing to report.
   */
  public function testOtherMethodIsNotConcerned(): void {
    $this->setMethod('browser_print');
    $this->assertSame([], vactory_quiz_certificat_requirements('runtime'));
  }

}
