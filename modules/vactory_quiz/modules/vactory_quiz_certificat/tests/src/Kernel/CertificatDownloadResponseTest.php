<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the answer to the download of a certificate (Redmine #71157).
 *
 * Requests go through the HTTP kernel. The owner and the users with the
 * "administer site configuration" permission download the file. Any other
 * request in the folder of the certificates gets the same "not found" answer,
 * whether the file exists or not. Other private files keep the answer of
 * core.
 *
 * @group vactory_quiz_certificat
 */
class CertificatDownloadResponseTest extends KernelTestBase {

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
   * Download path of the certificate recorded for the owner.
   */
  protected const PATH = '/system/files/quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf';

  /**
   * Download path of a certificate that does not exist.
   */
  protected const MISSING = '/system/files/quiz_certificat/2026-11/Missing-Certif-2.pdf';

  /**
   * Download path of a private file that is not a certificate.
   */
  protected const OTHER = '/system/files/other/file.pdf';

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
   * A user with the "administer site configuration" permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $administrator;

  /**
   * A logged-in user who has no certificate.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $other;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container->register('stream_wrapper.private', PrivateStream::class)
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem() {
    parent::setUpFilesystem();
    mkdir($this->siteDirectory . '/private', 0775);
    $this->setSetting('file_private_path', $this->siteDirectory . '/private');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('vactory_quiz_history', ['vactory_quiz_history']);
    $this->installConfig(['system', 'user']);
    // The first account is the super user: not used.
    $this->createUser();
    $this->owner = $this->createUser();
    $this->administrator = $this->createUser(['administer site configuration']);
    $this->other = $this->createUser(['access content']);
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
    mkdir('private://quiz_certificat/2026-11', 0775, TRUE);
    file_put_contents(self::URI, '%PDF-1.4');
    mkdir('private://other', 0775, TRUE);
    file_put_contents('private://other/file.pdf', '%PDF-1.4');
    mkdir('private://quiz_certificat_2', 0775, TRUE);
    file_put_contents('private://quiz_certificat_2/file.pdf', '%PDF-1.4');
  }

  /**
   * Handles a GET request the way the site does.
   */
  protected function get(string $uri): Response {
    return $this->container->get('http_kernel')->handle(Request::create($uri));
  }

  /**
   * Asserts that two responses have the same class and the same status.
   */
  protected function assertSameAnswer(Response $expected, Response $actual): void {
    $this->assertSame(get_class($expected), get_class($actual));
    $this->assertSame($expected->getStatusCode(), $actual->getStatusCode());
  }

  /**
   * Returns the accounts that make a request, the anonymous user included.
   */
  protected function everybody(): array {
    return [
      'owner' => $this->owner,
      'administrator' => $this->administrator,
      'other' => $this->other,
      'anonymous' => new AnonymousUserSession(),
    ];
  }

  /**
   * The owner downloads the certificate.
   */
  public function testOwnerDownloads(): void {
    $this->setCurrentUser($this->owner);
    $response = $this->get(self::PATH);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertInstanceOf(BinaryFileResponse::class, $response);
    $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
  }

  /**
   * A user with the administration permission downloads the certificate.
   */
  public function testAdministratorDownloads(): void {
    $this->setCurrentUser($this->administrator);
    $response = $this->get(self::PATH);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertInstanceOf(BinaryFileResponse::class, $response);
  }

  /**
   * Another logged-in user gets "not found", as for a missing certificate.
   */
  public function testOtherUserGetsNotFound(): void {
    $this->setCurrentUser($this->other);
    $existing = $this->get(self::PATH);
    $missing = $this->get(self::MISSING);
    $this->assertSame(404, $existing->getStatusCode());
    $this->assertSame(404, $missing->getStatusCode());
    $this->assertSameAnswer($missing, $existing);
    // The form of the URL with a "file" parameter.
    $response = $this->get('/system/files?file=quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf');
    $this->assertSameAnswer($missing, $response);
  }

  /**
   * An anonymous user gets "not found", as for a missing certificate.
   */
  public function testAnonymousGetsNotFound(): void {
    $this->setCurrentUser(new AnonymousUserSession());
    $existing = $this->get(self::PATH);
    $missing = $this->get(self::MISSING);
    $this->assertSame(404, $existing->getStatusCode());
    $this->assertSame(404, $missing->getStatusCode());
    $this->assertSameAnswer($missing, $existing);
  }

  /**
   * The owner of a certificate gets "not found" for the other ones.
   */
  public function testOwnerGetsNotFoundForOtherCertificates(): void {
    file_put_contents('private://quiz_certificat/2026-11/John-Doe-Certif-3.pdf', '%PDF-1.4');
    $this->setCurrentUser($this->owner);
    $existing = $this->get('/system/files/quiz_certificat/2026-11/John-Doe-Certif-3.pdf');
    $this->assertSame(404, $existing->getStatusCode());
    $this->assertSameAnswer($this->get(self::MISSING), $existing);
  }

  /**
   * Other paths that lead to the folder get the same "not found" answer.
   */
  public function testOtherPathsToTheFolder(): void {
    $this->setCurrentUser($this->other);
    $missing = $this->get(self::MISSING);
    $targets = [
      './quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf',
      '/quiz_certificat//2026-11/Jane-Doe-Certif-1.pdf',
      'other/../quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf',
    ];
    foreach ($targets as $target) {
      $existing = $this->get('/system/files?file=' . $target);
      $this->assertSame(404, $existing->getStatusCode(), $target);
      $this->assertSameAnswer($missing, $existing);
      $absent = $this->get('/system/files?file=' . str_replace('Jane-Doe-Certif-1', 'Missing-Certif-2', $target));
      $this->assertSameAnswer($missing, $absent);
    }
  }

  /**
   * A ".." segment after the folder never gives a file out of the folder.
   */
  public function testParentSegmentOutOfTheFolder(): void {
    $path = '/system/files?file=quiz_certificat/../other/file.pdf';
    $absent = '/system/files?file=quiz_certificat/../other/missing.pdf';
    foreach ($this->everybody() as $name => $account) {
      $this->setCurrentUser($account);
      $response = $this->get($path);
      $this->assertSame(404, $response->getStatusCode(), $name);
      $this->assertNotInstanceOf(BinaryFileResponse::class, $response);
      $this->assertSameAnswer($this->get($absent), $response);
    }

    // Same answer when core gives the path as it is to hook_file_download()
    // (see the "file_sa_core_2023_005_schemes" setting).
    $this->setSetting('file_sa_core_2023_005_schemes', ['private']);
    foreach ($this->everybody() as $name => $account) {
      $this->setCurrentUser($account);
      $response = $this->get($path);
      $this->assertSame(404, $response->getStatusCode(), $name);
      $this->assertNotInstanceOf(BinaryFileResponse::class, $response);
      $this->assertSameAnswer($this->get($absent), $response);
    }
  }

  /**
   * A ".." segment inside the folder.
   *
   * Core removes the segment before hook_file_download(): the request is the
   * one of the certificate itself. When core is set to give the path as it
   * is, nobody downloads it.
   */
  public function testParentSegmentInsideTheFolder(): void {
    $path = '/system/files?file=quiz_certificat/2026-11/../2026-11/Jane-Doe-Certif-1.pdf';
    $expected = [
      'owner' => 200,
      'administrator' => 200,
      'other' => 404,
      'anonymous' => 404,
    ];
    foreach ($this->everybody() as $name => $account) {
      $this->setCurrentUser($account);
      $this->assertSame($expected[$name], $this->get($path)->getStatusCode(), $name);
    }

    $this->setSetting('file_sa_core_2023_005_schemes', ['private']);
    foreach ($this->everybody() as $name => $account) {
      $this->setCurrentUser($account);
      $response = $this->get($path);
      $this->assertSame(404, $response->getStatusCode(), $name);
      $this->assertNotInstanceOf(BinaryFileResponse::class, $response);
    }
  }

  /**
   * Other private files keep the answer of core.
   */
  public function testOtherPrivateFilesAreUnchanged(): void {
    foreach ($this->everybody() as $name => $account) {
      $this->setCurrentUser($account);
      $this->assertSame(403, $this->get(self::OTHER)->getStatusCode(), $name);
      $this->assertSame(404, $this->get('/system/files/other/missing.pdf')->getStatusCode(), $name);
      // A folder whose name only starts like the one of the certificates.
      $this->assertSame(403, $this->get('/system/files/quiz_certificat_2/file.pdf')->getStatusCode(), $name);
      // A file of that name out of the folder.
      $this->assertSame(404, $this->get('/system/files/other/quiz_certificat/file.pdf')->getStatusCode(), $name);
    }
  }

  /**
   * The "not found" answer is not stored for the other users.
   *
   * The same URL is a download for the owner of the certificate. The page
   * caches do not run from the command line: what they read is checked.
   */
  public function testNotFoundIsNotCacheable(): void {
    $this->setCurrentUser($this->other);
    $existing = $this->get(self::PATH);
    $missing = $this->get(self::MISSING);
    foreach ([$existing, $missing] as $response) {
      $this->assertSame(404, $response->getStatusCode());
      // Dynamic page cache: a response whose max-age is 0 is not stored.
      $this->assertInstanceOf(CacheableResponseInterface::class, $response);
      $this->assertSame(0, $response->getCacheableMetadata()->getCacheMaxAge());
      // Browsers and proxies.
      $cache_control = $response->headers->get('Cache-Control');
      $this->assertStringContainsString('no-cache', $cache_control);
      $this->assertStringContainsString('private', $cache_control);
      $this->assertStringNotContainsString('public', $cache_control);
    }
    $this->assertSame($missing->headers->get('Cache-Control'), $existing->headers->get('Cache-Control'));

    $this->setCurrentUser($this->owner);
    $response = $this->get(self::PATH);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertInstanceOf(BinaryFileResponse::class, $response);
    $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
  }

}
