<?php

namespace Drupal\vactory_quiz_certificat\EventSubscriber;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Answers "not found" to a refused download in the folder of certificates.
 *
 * A certificate is downloaded by its owner or by a user with the
 * "administer site configuration" permission (see
 * vactory_quiz_certificat_file_download()). For any other request, the
 * download routes of core answer "access denied" for a file that exists and
 * "not found" otherwise: in "private://quiz_certificat", both get the same
 * "not found" answer.
 *
 * hook_file_download() can only refuse (403) or grant: the answer of core is
 * replaced here, on the download request only. Nothing changes for the other
 * private files.
 */
class CertificatNotFoundSubscriber implements EventSubscriberInterface {

  /**
   * The folder of the certificates, in the private files.
   */
  protected const FOLDER = 'quiz_certificat';

  /**
   * The core routes that serve a private file.
   */
  protected const ROUTES = ['system.files', 'system.private_file_download'];

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs the subscriber.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager, FileSystemInterface $file_system) {
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Before every subscriber of core that answers or logs a 403 or a 404
    // (the highest is Fast404ExceptionHtmlSubscriber, 200): they all handle
    // the request as they do for a file that does not exist.
    $events[KernelEvents::EXCEPTION][] = ['onException', 250];
    return $events;
  }

  /**
   * Replaces "access denied" by "not found" in the folder of certificates.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The exception event.
   */
  public function onException(ExceptionEvent $event): void {
    if (!$event->getThrowable() instanceof AccessDeniedHttpException) {
      return;
    }
    if ($this->isCertificatRequest($event->getRequest())) {
      $event->setThrowable(new NotFoundHttpException());
    }
  }

  /**
   * Tells whether a request asks for a file of the folder of certificates.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   TRUE for a download request that names the folder of the certificates,
   *   or a file stored in it.
   */
  protected function isCertificatRequest(Request $request): bool {
    $target = $request->query->all()['file'] ?? NULL;
    $is_private_download = in_array($request->attributes->get(RouteObjectInterface::ROUTE_NAME), static::ROUTES, TRUE)
      && $request->attributes->get('scheme', 'private') === 'private'
      && is_string($target);
    return $is_private_download
      && ($this->namesTheFolder($target) || $this->leadsToTheFolder($target));
  }

  /**
   * Tells whether a path, as it is asked, starts with the folder.
   *
   * @param string $target
   *   The path asked, relative to the private directory.
   *
   * @return bool
   *   TRUE when the first segment of the path is the folder of certificates.
   */
  protected function namesTheFolder(string $target): bool {
    $segments = array_diff(preg_split('@[/\\\\]@', $target), ['', '.']);
    return reset($segments) === static::FOLDER;
  }

  /**
   * Tells whether a path leads to the folder once resolved.
   *
   * @param string $target
   *   The path asked, relative to the private directory.
   *
   * @return bool
   *   TRUE when the URI core checks, or the file itself, is in the folder.
   */
  protected function leadsToTheFolder(string $target): bool {
    if (!$this->streamWrapperManager->isValidScheme('private')) {
      return FALSE;
    }
    // The URI core checks, the one given to hook_file_download().
    $uri = $this->streamWrapperManager->normalizeUri('private://' . $target);
    if (str_starts_with($uri, 'private://' . static::FOLDER . '/')) {
      return TRUE;
    }
    // The file itself, when another path leads to it.
    $folder = $this->fileSystem->realpath('private://' . static::FOLDER);
    $file = $this->fileSystem->realpath($uri);
    return $folder && $file && str_starts_with($file, $folder . DIRECTORY_SEPARATOR);
  }

}
