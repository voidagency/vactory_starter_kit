<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the download access of generated certificates (Redmine #71157).
 *
 * Certificates are written in "private://quiz_certificat": without a
 * hook_file_download() implementation nobody could download them.
 *
 * @group vactory_quiz_certificat
 */
class CertificatFileDownloadTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'vactory_quiz_history',
    'vactory_quiz_certificat',
  ];

  /**
   * URI of the certificate recorded for the owner.
   */
  protected const URI = 'private://quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf';

  /**
   * The user who obtained the certificate.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $owner;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('vactory_quiz_history', ['vactory_quiz_history']);
    // The first account is the super user: not used.
    $this->createUser();
    $this->owner = $this->createUser();
    $this->container->get('database')->insert('vactory_quiz_history')
      ->fields([
        'uid' => $this->owner->id(),
        'quiz_nid' => 1,
        'user_mark' => 10,
        'perfect_mark' => 10,
        'user_answers' => '[]',
        'time' => 1,
        'certificat' => self::URI,
      ])
      ->execute();
  }

  /**
   * Invokes hook_file_download() as core does for a private file.
   */
  protected function fileDownloadHeaders(string $uri): array {
    return $this->container->get('module_handler')->invokeAll('file_download', [$uri]);
  }

  /**
   * The owner of a certificate can download it.
   */
  public function testOwnerCanDownload(): void {
    $this->setCurrentUser($this->owner);
    $headers = $this->fileDownloadHeaders(self::URI);
    $this->assertSame('application/pdf', $headers['Content-Type'] ?? NULL);
  }

  /**
   * Other users and other files get no access from this module.
   */
  public function testNoAccessForOthers(): void {
    $this->setCurrentUser($this->createUser());
    $this->assertSame([], $this->fileDownloadHeaders(self::URI));

    $this->setUpCurrentUser(['uid' => 0]);
    $this->assertSame([], $this->fileDownloadHeaders(self::URI));

    $this->setCurrentUser($this->owner);
    $this->assertSame([], $this->fileDownloadHeaders('private://quiz_certificat/2026-11/Other-Certif-2.pdf'));
    $this->assertSame([], $this->fileDownloadHeaders('private://other/file.pdf'));
  }

}
