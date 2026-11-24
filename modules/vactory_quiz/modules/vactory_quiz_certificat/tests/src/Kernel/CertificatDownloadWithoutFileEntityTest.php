<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

require_once __DIR__ . '/CertificatDownloadMatrixTestBase.php';

/**
 * Downloads of a certificate on a site without File Entity (Redmine #71471).
 *
 * Same tests as on a site with File Entity: only the File module of core
 * answers for the files that have a file entity.
 *
 * @group vactory_quiz_certificat
 */
class CertificatDownloadWithoutFileEntityTest extends CertificatDownloadMatrixTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'vactory_quiz_history',
    'vactory_quiz_certificat',
    'vactory_quiz_certificat_grant_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('file_entity'));
  }

  /**
   * {@inheritdoc}
   */
  protected function siteConfigurations(): array {
    // The file permissions are the ones of File Entity: none here.
    return ['as installed' => []];
  }

  /**
   * {@inheritdoc}
   *
   * The answers of core: nobody downloads a private file that nothing
   * references, with or without file entity.
   */
  protected function expectedForOtherFiles(string $configuration): array {
    return [
      'owner' => [403, 403],
      'other' => [403, 403],
      'site configuration' => [403, 403],
      'administrator role' => [403, 403],
      'anonymous' => [403, 403],
    ];
  }

}
