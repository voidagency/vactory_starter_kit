<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

require_once __DIR__ . '/CertificatDownloadMatrixTestBase.php';

/**
 * Downloads of a certificate on a site with File Entity (Redmine #71471).
 *
 * File Entity is a dependency of the profile. It answers for every private
 * file, with or without file entity, from its own file permissions: the
 * download of a certificate must neither need one of them, nor be given by
 * one of them.
 *
 * @group vactory_quiz_certificat
 */
class CertificatDownloadWithFileEntityTest extends CertificatDownloadMatrixTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'file',
    'image',
    'views',
    'token',
    'file_entity',
    'vactory_quiz_history',
    'vactory_quiz_certificat',
    'vactory_quiz_certificat_grant_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->assertTrue($this->container->get('module_handler')->hasImplementations('file_download', 'file_entity'));
  }

  /**
   * {@inheritdoc}
   */
  protected function siteConfigurations(): array {
    return [
      'as installed' => [],
      'own private files' => [
        'authenticated' => ['view own private files'],
      ],
      'private files and bypass for a role' => [
        'files' => ['view private files', 'bypass file access'],
      ],
      'every file permission' => [
        'authenticated' => [
          'view own private files',
          'view private files',
          'bypass file access',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The answers of File Entity, measured on a site without this module: the
   * permissions on private files, or the administrator role.
   */
  protected function expectedForOtherFiles(string $configuration): array {
    $index = array_search($configuration, array_keys($this->siteConfigurations()), TRUE);
    // For each account, in the order of siteConfigurations(): the status of
    // the file with a file entity, then of the file without.
    $table = [
      'owner' => ['403 403', '200 200', '403 403', '200 200'],
      'other' => ['403 403', '403 200', '200 200', '200 200'],
      'site configuration' => ['403 403', '403 200', '403 403', '200 200'],
      'administrator role' => ['200 200', '200 200', '200 200', '200 200'],
      'anonymous' => ['403 403', '403 403', '403 403', '403 403'],
    ];
    return array_map(fn (array $statuses) => array_map('intval', explode(' ', $statuses[$index])), $table);
  }

}
