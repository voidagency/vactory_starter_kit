<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Downloads of a certificate by every kind of account (Redmine #71471).
 *
 * Requests go through the HTTP kernel and the download controller of core,
 * with every implementation of hook_file_download() of the enabled modules.
 * The same tests run on a site with the File Entity module, which decides the
 * download of every private file with its own file permissions, and on a site
 * without it (see the two classes that extend this one).
 *
 * The rule does not depend on the modules of the site, nor on the file
 * permissions of the accounts: the owner of a certificate and the accounts
 * with the "administer site configuration" permission download it, any other
 * account gets "not found", an anonymous user gets the login page.
 */
abstract class CertificatDownloadMatrixTestBase extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Download path of the certificate recorded for the owner.
   */
  protected const PATH = '/system/files/quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf';

  /**
   * Download path of a certificate that does not exist.
   */
  protected const MISSING = '/system/files/quiz_certificat/2026-11/Missing-Certif-2.pdf';

  /**
   * URI of the certificate recorded for the owner.
   */
  protected const URI = 'private://quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf';

  /**
   * URI of a private file that is not a certificate, with a file entity.
   */
  protected const MANAGED = 'private://other/managed.txt';

  /**
   * URI of a private file that is not a certificate, without file entity.
   */
  protected const UNMANAGED = 'private://other/unmanaged.txt';

  /**
   * The role of the "other" account, to which file permissions are given.
   */
  protected const ROLE = 'files';

  /**
   * The accounts that make the requests, by name.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $accounts = [];

  /**
   * Returns the file permissions of each site configuration to test.
   *
   * @return array
   *   For each configuration, by name: the permissions to grant, by role.
   */
  abstract protected function siteConfigurations(): array;

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
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('vactory_quiz_history', ['vactory_quiz_history']);
    $this->installConfig(['system', 'user']);
    // The first account is the super user: not used.
    $this->createUser();
    $this->createRole([], static::ROLE);
    $this->accounts = [
      'owner' => $this->createUser(),
      'site configuration' => $this->createUser([
        'administer site configuration',
      ]),
      'administrator role' => $this->createUser([], NULL, FALSE, [
        'roles' => [$this->createAdminRole()],
      ]),
      'other' => $this->createUser([], NULL, FALSE, [
        'roles' => [static::ROLE],
      ]),
      'anonymous' => new AnonymousUserSession(),
    ];
    $this->container->get('database')->insert('vactory_quiz_history')
      ->fields([
        'uid' => $this->accounts['owner']->id(),
        'quiz_nid' => 1,
        'user_mark' => 10,
        'perfect_mark' => 10,
        'user_answers' => '[]',
        'time' => 1,
        'certificat' => static::URI,
      ])
      ->execute();
    mkdir('private://quiz_certificat/2026-11', 0775, TRUE);
    file_put_contents(static::URI, '%PDF-1.4');
    mkdir('private://other', 0775, TRUE);
    file_put_contents(static::MANAGED, 'managed');
    file_put_contents(static::UNMANAGED, 'unmanaged');
    File::create([
      'uri' => static::MANAGED,
      'uid' => $this->accounts['owner']->id(),
      'status' => 1,
    ])->save();
  }

  /**
   * Handles a GET request the way the site does, for an account.
   */
  protected function getAs(string $name, string $uri): Response {
    $this->setCurrentUser($this->accounts[$name]);
    return $this->container->get('http_kernel')->handle(Request::create($uri));
  }

  /**
   * Gives the file permissions of a site configuration, and only them.
   *
   * @param array $permissions
   *   The permissions to grant, by role.
   */
  protected function applySiteConfiguration(array $permissions): void {
    foreach ([RoleInterface::AUTHENTICATED_ID, static::ROLE] as $id) {
      $role = Role::load($id);
      foreach ($role->getPermissions() as $permission) {
        $role->revokePermission($permission);
      }
      foreach ($permissions[$id] ?? [] as $permission) {
        $role->grantPermission($permission);
      }
      $role->save();
    }
    $this->container->get('entity_type.manager')->getAccessControlHandler('file')->resetCache();
  }

  /**
   * Asserts that a response is the download of the certificate.
   */
  protected function assertIsTheCertificate(Response $response, string $message): void {
    $this->assertSame(200, $response->getStatusCode(), $message);
    $this->assertInstanceOf(BinaryFileResponse::class, $response, $message);
    $this->assertSame('application/pdf', $response->headers->get('Content-Type'), $message);
    $this->assertSame('%PDF-1.4', file_get_contents($response->getFile()->getPathname()), $message);
    $this->assertStringContainsString('private', $response->headers->get('Cache-Control'), $message);
  }

  /**
   * Asserts the answer given to an account that does not get a certificate.
   *
   * "Not found" for a logged-in account, the login page for an anonymous
   * user: the same answer as for a certificate that does not exist.
   */
  protected function assertIsRefused(string $name, string $path, string $message): void {
    $response = $this->getAs($name, $path);
    $missing = $this->getAs($name, static::MISSING);
    $expected = $this->accounts[$name]->isAnonymous() ? 302 : 404;
    $this->assertNotInstanceOf(BinaryFileResponse::class, $response, $message);
    $this->assertSame($expected, $response->getStatusCode(), $message);
    $this->assertSame($missing->getStatusCode(), $response->getStatusCode(), $message);
    $this->assertSame(get_class($missing), get_class($response), $message);
    if ($expected === 302) {
      $this->assertStringContainsString('/user/login?destination=', $response->headers->get('Location'), $message);
      $this->assertSame(
        str_replace('Missing-Certif-2', 'Jane-Doe-Certif-1', $missing->headers->get('Location')),
        $response->headers->get('Location'),
        $message
      );
    }
  }

  /**
   * The owner and the accounts with the permission download the certificate.
   */
  public function testOwnerAndSiteConfigurationDownload(): void {
    foreach ($this->siteConfigurations() as $configuration => $permissions) {
      $this->applySiteConfiguration($permissions);
      foreach (['owner', 'site configuration', 'administrator role'] as $name) {
        $this->assertIsTheCertificate($this->getAs($name, static::PATH), "$configuration: $name");
      }
    }
  }

  /**
   * Any other account is refused, whatever its file permissions.
   */
  public function testOtherAccountsAreRefused(): void {
    $paths = [
      static::PATH,
      '/system/files?file=quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf',
    ];
    foreach ($this->siteConfigurations() as $configuration => $permissions) {
      $this->applySiteConfiguration($permissions);
      foreach ($paths as $path) {
        $this->assertIsRefused('other', $path, "$configuration: other, $path");
        $this->assertIsRefused('anonymous', $path, "$configuration: anonymous, $path");
      }
    }
  }

  /**
   * The owner downloads right after the refusal of another account.
   */
  public function testOwnerDownloadsAfterTheRefusalOfAnotherAccount(): void {
    foreach ($this->siteConfigurations() as $configuration => $permissions) {
      $this->applySiteConfiguration($permissions);
      foreach ([1, 2] as $round) {
        $this->assertIsRefused('other', static::PATH, "$configuration: other, round $round");
        $this->assertIsTheCertificate($this->getAs('owner', static::PATH), "$configuration: owner, round $round");
      }
    }
  }

  /**
   * Another path of the private files to the certificate is refused too.
   */
  public function testOtherPathsToTheCertificateAreRefused(): void {
    $targets = [
      'other/../quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf',
      'quiz_certificat/2026-11/../2026-11/Jane-Doe-Certif-1.pdf',
      './quiz_certificat//2026-11/Jane-Doe-Certif-1.pdf',
    ];
    foreach ($this->siteConfigurations() as $configuration => $permissions) {
      $this->applySiteConfiguration($permissions);
      foreach ($targets as $target) {
        $response = $this->getAs('other', '/system/files?file=' . $target);
        $this->assertSame(404, $response->getStatusCode(), "$configuration: $target");
        $this->assertNotInstanceOf(BinaryFileResponse::class, $response);
      }
    }
    // When core gives the path as it is to the modules (see the
    // "file_sa_core_2023_005_schemes" setting), a ".." segment is refused for
    // everybody.
    $this->setSetting('file_sa_core_2023_005_schemes', ['private']);
    foreach (array_keys($this->accounts) as $name) {
      $response = $this->getAs($name, '/system/files?file=' . $targets[1]);
      $this->assertNotInstanceOf(BinaryFileResponse::class, $response, $name);
      $this->assertSame(404, $response->getStatusCode(), $name);
    }
  }

  /**
   * A module that grants every private file does not grant a certificate.
   */
  public function testAnotherModuleCannotGrantTheCertificate(): void {
    $this->container->get('state')->set('vactory_quiz_certificat_grant_test.grant', TRUE);
    // The test module grants every private file, the certificate included.
    $headers = $this->container->get('module_handler')
      ->invoke('vactory_quiz_certificat_grant_test', 'file_download', [
        static::URI,
      ]);
    $this->assertSame('vactory_quiz_certificat_grant_test', $headers['X-Granted-By']);
    foreach ($this->siteConfigurations() as $configuration => $permissions) {
      $this->applySiteConfiguration($permissions);
      $this->assertIsRefused('other', static::PATH, "$configuration: other");
      $this->assertIsRefused('anonymous', static::PATH, "$configuration: anonymous");
      // The headers of the other module replace the type of the file.
      $response = $this->getAs('owner', static::PATH);
      $this->assertSame(200, $response->getStatusCode(), "$configuration: owner");
      $this->assertInstanceOf(BinaryFileResponse::class, $response, "$configuration: owner");
    }
  }

  /**
   * A file entity of the certificate does not give it to another account.
   *
   * The module does not store a file entity for a certificate; a site could.
   * Its owner is not the owner of the certificate here.
   */
  public function testFileEntityOfTheCertificate(): void {
    $file = File::create([
      'uri' => static::URI,
      'uid' => $this->accounts['other']->id(),
      'status' => 1,
    ]);
    $file->save();
    foreach ($this->siteConfigurations() as $configuration => $permissions) {
      $this->applySiteConfiguration($permissions);
      $this->assertIsRefused('other', static::PATH, "$configuration: other");
      $this->assertIsTheCertificate($this->getAs('owner', static::PATH), "$configuration: owner");
      $this->assertIsTheCertificate($this->getAs('site configuration', static::PATH), "$configuration: site configuration");
      foreach (['view', 'download'] as $operation) {
        $this->assertFalse($file->access($operation, $this->accounts['other']), "$configuration: $operation, other");
        $this->assertTrue($file->access($operation, $this->accounts['owner']), "$configuration: $operation, owner");
      }
    }
  }

  /**
   * The module takes no part in the access to the other private files.
   */
  public function testOtherPrivateFilesAreNotConcerned(): void {
    $module_handler = $this->container->get('module_handler');
    $managed = File::load(1);
    $this->assertSame(static::MANAGED, $managed->getFileUri());
    foreach ($this->accounts as $name => $account) {
      $this->setCurrentUser($account);
      $uris = [
        static::MANAGED,
        static::UNMANAGED,
        'private://quiz_certificat_2/file.pdf',
      ];
      foreach ($uris as $uri) {
        $result = $module_handler->invoke('vactory_quiz_certificat', 'file_download', [
          $uri,
        ]);
        $this->assertNull($result, "$name: $uri");
      }
      foreach (['view', 'download', 'update', 'delete'] as $operation) {
        $result = $module_handler->invoke('vactory_quiz_certificat', 'file_access', [
          $managed,
          $operation,
          $account,
        ]);
        $this->assertTrue($result->isNeutral(), "$name: $operation");
      }
    }
    foreach ($this->siteConfigurations() as $configuration => $permissions) {
      $this->applySiteConfiguration($permissions);
      foreach ($this->expectedForOtherFiles($configuration) as $name => $statuses) {
        $this->assertSame($statuses[0], $this->getAs($name, '/system/files/other/managed.txt')->getStatusCode(), "$configuration: managed file, $name");
        $this->assertSame($statuses[1], $this->getAs($name, '/system/files/other/unmanaged.txt')->getStatusCode(), "$configuration: unmanaged file, $name");
      }
    }
  }

  /**
   * Returns the answers of the site for the other private files.
   *
   * @param string $configuration
   *   The name of a site configuration.
   *
   * @return array
   *   For each account, by name: the status of the download of the file with
   *   a file entity, owned by the owner of the certificate, then of the file
   *   without file entity.
   */
  abstract protected function expectedForOtherFiles(string $configuration): array;

}
