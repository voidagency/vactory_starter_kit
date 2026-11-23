<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\vactory_quiz_certificat\EventSubscriber\CertificatLoginRedirectSubscriber;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the link of a certificate opened logged out (Redmine #71440).
 *
 * The link sent by mail is the private download URL of the certificate. An
 * anonymous user is sent to the login page and comes back to the certificate
 * once logged in, whether the file exists or not. Nothing changes for a
 * logged-in user, nor for the other private files.
 *
 * @group vactory_quiz_certificat
 */
class CertificatAnonymousDownloadTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'language',
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
    $this->installConfig(['system', 'user', 'language']);
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
    mkdir('private://quiz_certificat/2026-11', 0775, TRUE);
    file_put_contents(self::URI, '%PDF-1.4');
    mkdir('private://other', 0775, TRUE);
    file_put_contents('private://other/file.pdf', '%PDF-1.4');
    $this->setCurrentUser(new AnonymousUserSession());
  }

  /**
   * Handles a GET request the way the site does.
   */
  protected function get(string $uri): Response {
    return $this->container->get('http_kernel')->handle(Request::create($uri));
  }

  /**
   * Asserts that a response is the redirect to the login page.
   */
  protected function assertLoginRedirect(Response $response, string $login = '/user/login'): void {
    $this->assertSame(302, $response->getStatusCode());
    $location = parse_url($response->headers->get('Location'));
    $this->assertSame($login, $location['path']);
    parse_str($location['query'] ?? '', $query);
    // The destination is the certificate, and nothing else.
    $this->assertSame(['destination' => self::PATH], $query);
    $cache_control = $response->headers->get('Cache-Control');
    $this->assertStringContainsString('no-cache', $cache_control);
    $this->assertStringContainsString('private', $cache_control);
    $this->assertStringNotContainsString('public', $cache_control);
  }

  /**
   * An anonymous user is sent to the login page, then to the certificate.
   */
  public function testAnonymousIsSentToTheLoginPage(): void {
    $this->assertLoginRedirect($this->get(self::PATH));

    // Once logged in, the destination is the download of the certificate.
    $this->setCurrentUser($this->owner);
    $response = $this->get(self::PATH);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertInstanceOf(BinaryFileResponse::class, $response);
    $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
  }

  /**
   * The answer is the same for a certificate that does not exist.
   */
  public function testMissingCertificateGivesTheSameRedirect(): void {
    $existing = $this->get(self::PATH);
    $missing = $this->get(self::MISSING);
    $this->assertSame(302, $missing->getStatusCode());
    $this->assertSame($existing->getStatusCode(), $missing->getStatusCode());
    $this->assertSame(
      str_replace('Jane-Doe-Certif-1', 'Missing-Certif-2', $existing->headers->get('Location')),
      $missing->headers->get('Location')
    );
    $this->assertSame($existing->headers->get('Cache-Control'), $missing->headers->get('Cache-Control'));
  }

  /**
   * The redirect is kept out of the page caches.
   *
   * The same URL is a download for the owner of the certificate. The page
   * caches do not run from the command line: their policy is checked.
   */
  public function testRedirectIsNotCacheable(): void {
    $policy = $this->container->get('page_cache_response_policy');
    $request = Request::create(self::PATH);
    $this->assertNotSame('deny', $policy->check(new Response(), $request));

    $response = $this->get(self::PATH);
    $this->assertLoginRedirect($response);
    // Page cache.
    $this->assertSame('deny', $policy->check($response, $request));
    // Dynamic page cache.
    $this->assertInstanceOf(CacheableResponseInterface::class, $response);
    $this->assertSame(0, $response->getCacheableMetadata()->getCacheMaxAge());

    $this->setCurrentUser($this->owner);
    $this->assertSame(200, $this->get(self::PATH)->getStatusCode());
  }

  /**
   * The owner downloads the certificate, another user is refused.
   */
  public function testLoggedInUsersAreNotRedirected(): void {
    $this->setCurrentUser($this->owner);
    $this->assertSame(200, $this->get(self::PATH)->getStatusCode());

    $this->setCurrentUser($this->createUser(['access content']));
    $this->assertSame(403, $this->get(self::PATH)->getStatusCode());
    $this->assertSame(404, $this->get(self::MISSING)->getStatusCode());
  }

  /**
   * Other private files keep the answer of core.
   */
  public function testOtherPrivateFilesAreUnchanged(): void {
    $this->assertSame(403, $this->get(self::OTHER)->getStatusCode());
    $this->assertSame(404, $this->get('/system/files/other/missing.pdf')->getStatusCode());
    // A folder whose name only starts like the one of the certificates.
    $this->assertSame(404, $this->get('/system/files/quiz_certificat_2/file.pdf')->getStatusCode());
    // A path with a ".." segment is left to core.
    $response = $this->get('/system/files?file=other/../quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf');
    $this->assertNotSame(302, $response->getStatusCode());
  }

  /**
   * The destination never comes from the request.
   */
  public function testDestinationOfTheRequestIsIgnored(): void {
    $this->assertLoginRedirect($this->get(self::PATH . '?destination=/admin'));
    $this->assertLoginRedirect($this->get(self::PATH . '?destination=https://example.org/'));
    // The form of the URL with a "file" parameter gives the same local path.
    $this->assertLoginRedirect($this->get('/system/files?file=quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf'));
  }

  /**
   * With a language prefix, the login page keeps the language.
   */
  public function testLanguagePrefix(): void {
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('language.negotiation')
      ->set('url.prefixes', ['en' => 'en', 'fr' => 'fr'])
      ->save();
    $this->container->get('kernel')->rebuildContainer();
    $this->container = $this->container->get('kernel')->getContainer();
    $this->container->get('router.builder')->rebuild();
    $this->setCurrentUser(new AnonymousUserSession());

    $this->assertLoginRedirect($this->get('/fr' . self::PATH), '/fr/user/login');
    // The link sent by mail has no prefix: default language.
    $this->assertLoginRedirect($this->get(self::PATH), '/en/user/login');
  }

  /**
   * No redirect where vactory_decoupled sends the login page to the front.
   */
  public function testNoRedirectWhereTheLoginPageIsNotServed(): void {
    $settings = Settings::getAll();
    $settings['BASE_FRONTEND_URL'] = 'https://www.example.org';
    new Settings($settings);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->willReturn(TRUE);
    $subscriber = new CertificatLoginRedirectSubscriber(
      $this->container->get('current_user'),
      $this->container->get('stream_wrapper_manager'),
      $this->container->get('file_url_generator'),
      $this->container->get('page_cache_kill_switch'),
      $module_handler
    );
    $responses = [];
    foreach (['api.example.org', 'bo.example.org'] as $host) {
      $request = Request::create('http://' . $host . '/system/files', 'GET', [
        'file' => 'quiz_certificat/2026-11/Jane-Doe-Certif-1.pdf',
      ]);
      $request->attributes->set('_route', 'system.files');
      $event = new ExceptionEvent(
        $this->container->get('http_kernel'),
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        new AccessDeniedHttpException()
      );
      $subscriber->onException($event);
      $responses[$host] = $event->getResponse();
    }
    $this->assertNull($responses['api.example.org']);
    $this->assertNotNull($responses['bo.example.org']);
    $this->assertSame(302, $responses['bo.example.org']->getStatusCode());
  }

}
