<?php

namespace Drupal\Tests\vactory_quiz_certificat\ExistingSite;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Download of a certificate over HTTP, on the site (Redmine #71471).
 *
 * The site has the modules of the profile, File Entity included: its file
 * permissions neither give nor take the download of a certificate. The owner
 * downloads it, another account gets "not found" whatever its file
 * permissions, an anonymous user gets the login page.
 *
 * @group vactory_quiz_certificat
 */
class CertificatDownloadTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = [
    'vactory_quiz',
    'vactory_quiz_history',
    'vactory_html2pdf',
    'vactory_quiz_certificat',
  ];

  /**
   * URI of the certificate written for the test.
   *
   * @var string
   */
  protected $uri;

  /**
   * Download path of the certificate.
   *
   * @var string
   */
  protected $path;

  /**
   * The owner of the certificate.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $owner;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if (!$this->container->get('stream_wrapper_manager')->isValidScheme('private')) {
      $this->markTestSkipped('The site has no private files.');
    }
    $name = 'Test-71471-Certif-' . $this->randomMachineName(12) . '.pdf';
    $directory = 'private://quiz_certificat/' . date('Y-m');
    $this->container->get('file_system')->prepareDirectory($directory, 1);
    $this->uri = $directory . '/' . $name;
    $this->path = '/system/files/quiz_certificat/' . date('Y-m') . '/' . $name;
    file_put_contents($this->uri, "%PDF-1.4\n% Test #71471\n");
    $this->owner = $this->createUser();
    $this->container->get('database')->insert('vactory_quiz_history')
      ->fields([
        'uid' => $this->owner->id(),
        'quiz_nid' => 0,
        'user_mark' => 10,
        'perfect_mark' => 10,
        'user_answers' => '[]',
        'time' => time(),
        'certificat' => $this->uri,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->uri) {
      $this->container->get('database')->delete('vactory_quiz_history')
        ->condition('certificat', $this->uri)
        ->execute();
      @unlink($this->uri);
    }
    parent::tearDown();
  }

  /**
   * The owner downloads the certificate, other accounts get "not found".
   */
  public function testOwnerDownloadsOtherAccountsDoNot(): void {
    $this->assertNotFound([]);
    $this->assertNotFound(['view own private files']);
    $this->assertNotFound(['view private files', 'bypass file access']);
    $this->assertNotFound(['bypass file access']);
    $this->assertIsTheCertificate($this->owner);
    $site_configuration = $this->createUser([
      'administer site configuration',
    ]);
    $this->assertIsTheCertificate($site_configuration);
  }

  /**
   * An anonymous user is sent to the login page, back to the certificate.
   */
  public function testAnonymousIsSentToTheLoginPage(): void {
    $response = $this->request(NULL);
    $this->assertSame(302, $response->getStatusCode());
    $location = $response->getHeaderLine('Location');
    $this->assertStringContainsString('login?destination=' . $this->path, $location);
  }

  /**
   * Asserts that an account downloads the certificate.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account created by createUser().
   */
  protected function assertIsTheCertificate(AccountInterface $account): void {
    $response = $this->request($account);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
    $this->assertStringStartsWith('%PDF', (string) $response->getBody());
  }

  /**
   * Asserts that an account with file permissions gets "not found".
   *
   * @param string[] $permissions
   *   The permissions of the account.
   */
  protected function assertNotFound(array $permissions): void {
    $label = implode(', ', $permissions) ?: 'no permission';
    $response = $this->request($this->createUser($permissions));
    $this->assertStringStartsNotWith('%PDF', (string) $response->getBody(), $label);
    $this->assertNotSame('application/pdf', $response->getHeaderLine('Content-Type'), $label);
    // On a decoupled site, the "not found" page of Drupal may be answered by
    // a redirect to the same path on the front (vactory_decoupled).
    $front = rtrim((string) Settings::get('BASE_FRONTEND_URL', ''), '/');
    if ($response->getStatusCode() === 302 && $front !== '') {
      $this->assertSame($front . $this->path, $response->getHeaderLine('Location'), $label);
      return;
    }
    $this->assertSame(404, $response->getStatusCode(), $label);
  }

  /**
   * Requests the certificate, redirects not followed.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Account created by createUser(), NULL for an anonymous request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response of the site.
   */
  protected function request(?AccountInterface $account): ResponseInterface {
    $headers = $account ? ['Cookie' => $this->logIn($account)] : [];
    return $this->client()->get($this->baseUrl . $this->path, [
      'headers' => $headers,
    ]);
  }

  /**
   * Opens a session with the one-time login link of an account.
   *
   * The login form is not used: on a decoupled site, it and the user page it
   * leads to may redirect to the front, which is not there during the tests.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account created by createUser().
   *
   * @return string
   *   The session cookie, as sent in a "Cookie" header.
   */
  protected function logIn(AccountInterface $account): string {
    $response = $this->client()->get(user_pass_reset_url($account) . '/login');
    $this->assertSame(302, $response->getStatusCode(), 'One-time login.');
    $cookies = preg_grep('/^S?SESS\w+=/', $response->getHeader('Set-Cookie'));
    $this->assertNotEmpty($cookies, 'Session cookie.');
    return strstr(reset($cookies), ';', TRUE) ?: reset($cookies);
  }

  /**
   * Returns an HTTP client that does not follow redirects.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The client.
   */
  protected function client(): ClientInterface {
    return $this->container->get('http_client_factory')->fromOptions([
      'allow_redirects' => FALSE,
      'http_errors' => FALSE,
      'verify' => FALSE,
    ]);
  }

}
