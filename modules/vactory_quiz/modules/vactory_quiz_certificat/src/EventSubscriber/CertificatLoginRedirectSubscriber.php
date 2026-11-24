<?php

namespace Drupal\vactory_quiz_certificat\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sends an anonymous user who asks for a certificate to the login page.
 *
 * A certificate is downloaded by its owner, logged in on the Drupal site (see
 * vactory_quiz_certificat_file_download()). The link sent by mail is often
 * opened logged out: instead of the "not found" page, the user gets the
 * login page and comes back to the certificate once logged in.
 *
 * Only an anonymous request for a file of "private://quiz_certificat" is
 * concerned. The answer is the same whether the file exists or not. Nothing
 * changes for a logged-in user, nor for the other private files.
 */
class CertificatLoginRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The folder of the certificates.
   */
  protected const FOLDER = 'private://quiz_certificat/';

  /**
   * The core routes that serve a private file.
   */
  protected const ROUTES = ['system.files', 'system.private_file_download'];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   The page cache kill switch.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    AccountInterface $current_user,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    FileUrlGeneratorInterface $file_url_generator,
    KillSwitch $kill_switch,
    ModuleHandlerInterface $module_handler
  ) {
    $this->currentUser = $current_user;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileUrlGenerator = $file_url_generator;
    $this->killSwitch = $kill_switch;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // After CertificatNotFoundSubscriber (250) and the subscribers of core
    // that log the exception (50) or answer an authentication challenge (75,
    // 80); before the ones that build the 403 and 404 pages (0 and below).
    $events[KernelEvents::EXCEPTION][] = ['onException', 25];
    return $events;
  }

  /**
   * Redirects an anonymous request for a certificate to the login page.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The exception event.
   */
  public function onException(ExceptionEvent $event): void {
    $exception = $event->getThrowable();
    $is_refusal = $exception instanceof AccessDeniedHttpException
      || $exception instanceof NotFoundHttpException;
    if (!$is_refusal || !$event->isMainRequest() || !$this->currentUser->isAnonymous()) {
      return;
    }
    $request = $event->getRequest();
    $uri = $this->certificatUri($request);
    if ($uri === NULL || $this->loginPageIsNotServed($request)) {
      return;
    }

    // The destination is built here, from the URI of the file: a local path,
    // the one of the link sent by mail. It is never read from the request,
    // and a "destination" parameter of the request must not replace the
    // redirect (see RedirectResponseSubscriber::checkRedirectUrl()).
    $request->query->remove('destination');
    $destination = $this->fileUrlGenerator->generateString($uri);
    $login = Url::fromRoute('user.login', [], [
      'query' => ['destination' => $destination],
    ])->toString(TRUE);

    // Never cached: the same URL is a download for its owner. The kill switch
    // keeps the response out of the page cache, the max-age out of the
    // dynamic page cache.
    $this->killSwitch->trigger();
    $response = new LocalRedirectResponse($login->getGeneratedUrl());
    $response->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
    $event->setResponse($response);
  }

  /**
   * Returns the URI of the certificate a request asks for.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return string|null
   *   The URI, in the folder of the certificates; NULL when the request is
   *   not the download of a certificate.
   */
  protected function certificatUri(Request $request): ?string {
    $target = $request->query->get('file');
    $is_private_download = in_array($request->attributes->get(RouteObjectInterface::ROUTE_NAME), static::ROUTES, TRUE)
      && $request->attributes->get('scheme', 'private') === 'private'
      && is_string($target)
      && $this->streamWrapperManager->isValidScheme('private');
    // A path with a ".." segment is not redirected: it gets the "not found"
    // answer of CertificatNotFoundSubscriber.
    if (!$is_private_download || in_array('..', preg_split('@[/\\\\]@', $target), TRUE)) {
      return NULL;
    }
    // Same URI as the one core checks and serves.
    $uri = $this->streamWrapperManager->normalizeUri('private://' . $target);
    return str_starts_with($uri, static::FOLDER) ? $uri : NULL;
  }

  /**
   * Tells whether the login page is replaced by a redirect to the front.
   *
   * On a host named "api.…", or on the host of the media, vactory_decoupled
   * redirects every HTML page of Drupal, the login page included, to the
   * decoupled front (vactory_decoupled_preprocess_page()). The user could not
   * log in there: the answer of core is kept.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   TRUE when the login page cannot be displayed on this host.
   */
  protected function loginPageIsNotServed(Request $request): bool {
    if (!$this->moduleHandler->moduleExists('vactory_decoupled') || !Settings::get('BASE_FRONTEND_URL', '')) {
      return FALSE;
    }
    $host = $request->getHttpHost();
    if (str_starts_with($host, 'api.')) {
      return TRUE;
    }
    $media_host = parse_url((string) Settings::get('BASE_MEDIA_URL', ''), PHP_URL_HOST);
    return !empty($media_host) && $media_host === $host;
  }

}
