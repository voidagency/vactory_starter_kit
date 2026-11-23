<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the download access of generated certificates (Redmine #71157).
 *
 * Certificates are written in "private://quiz_certificat": without a
 * hook_file_download() implementation nobody could download them. The owner
 * and the users with the "administer site configuration" permission can.
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
   * A user with the administration permission downloads any certificate.
   */
  public function testAdministratorCanDownload(): void {
    $administrator = $this->createUser(['administer site configuration']);
    $this->setCurrentUser($administrator);
    $headers = $this->fileDownloadHeaders(self::URI);
    $this->assertSame('application/pdf', $headers['Content-Type'] ?? NULL);
    // Files of other modules are not concerned.
    $this->assertSame([], $this->fileDownloadHeaders('private://other/file.pdf'));
  }

  /**
   * Another user, without the administration permission, gets no access.
   */
  public function testOtherUserCannotDownload(): void {
    $this->setCurrentUser($this->createUser(['access content']));
    $this->assertSame([], $this->fileDownloadHeaders(self::URI));
  }

  /**
   * An anonymous user never gets access.
   */
  public function testAnonymousCannotDownload(): void {
    $this->setCurrentUser(new AnonymousUserSession());
    $this->assertSame([], $this->fileDownloadHeaders(self::URI));
  }

  /**
   * Not even when the anonymous role holds the administration permission.
   */
  public function testAnonymousWithPermissionCannotDownload(): void {
    $this->installConfig(['user']);
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('administer site configuration')
      ->save();
    $anonymous = new AnonymousUserSession();
    $this->setCurrentUser($anonymous);
    $this->assertTrue($anonymous->hasPermission('administer site configuration'));
    $this->assertSame([], $this->fileDownloadHeaders(self::URI));
  }

  /**
   * A URI with a ".." segment is refused for everybody.
   */
  public function testParentSegmentIsRefused(): void {
    $in_folder = 'private://quiz_certificat/2026-11/../2026-11/Jane-Doe-Certif-1.pdf';
    $this->container->get('database')->update('vactory_quiz_history')
      ->fields(['certificat' => $in_folder])
      ->execute();
    $uris = [
      $in_folder,
      'private://quiz_certificat/../other/file.pdf',
      'private://quiz_certificat/2026-11\\..\\..\\file.pdf',
    ];
    $accounts = [
      $this->owner,
      $this->createUser(['administer site configuration']),
      $this->createUser(['access content']),
      new AnonymousUserSession(),
    ];
    foreach ($accounts as $account) {
      $this->setCurrentUser($account);
      foreach ($uris as $uri) {
        $this->assertSame([-1], $this->fileDownloadHeaders($uri), $uri);
      }
    }
    // A name that only contains dots is not a ".." segment.
    $this->setCurrentUser($accounts[1]);
    $headers = $this->fileDownloadHeaders('private://quiz_certificat/2026-11/A..B.pdf');
    $this->assertSame('application/pdf', $headers['Content-Type'] ?? NULL);
  }

  /**
   * The owner gets no access to other files from this module.
   */
  public function testNoAccessToOtherFiles(): void {
    $this->setCurrentUser($this->owner);
    $this->assertSame([], $this->fileDownloadHeaders('private://quiz_certificat/2026-11/Other-Certif-2.pdf'));
    $this->assertSame([], $this->fileDownloadHeaders('private://other/file.pdf'));
  }

}
