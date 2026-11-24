<?php

namespace Drupal\vactory_quiz_certificat\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Decides who downloads a file of the folder of the certificates.
 *
 * Certificates are generated in "private://quiz_certificat". A user downloads
 * the certificates recorded for that user in the quiz history. A user with
 * the "administer site configuration" permission, the one of the settings
 * forms of the quiz modules, downloads any of them (support). Nobody else
 * does, whatever the file permissions of the account.
 *
 * The same decision is given to every access check of a private file: the
 * download hook (vactory_quiz_certificat_file_download()) and the access to a
 * file entity (vactory_quiz_certificat_file_access()), which is the check of
 * the modules that manage files, File Entity for instance.
 */
class CertificatAccess {

  /**
   * The folder of the certificates.
   */
  public const FOLDER = 'private://quiz_certificat/';

  /**
   * The permission that gives access to every certificate.
   */
  public const PERMISSION = 'administer site configuration';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * Constructs the service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(Connection $database, StreamWrapperManagerInterface $stream_wrapper_manager, FileSystemInterface $file_system) {
    $this->database = $database;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * Tells whether a URI is in the folder of the certificates.
   *
   * @param string $uri
   *   The URI of a file.
   *
   * @return bool
   *   TRUE when the URI names the folder, as it is given or once normalized,
   *   or when another path of the private files leads to a file stored in it
   *   ("other/../quiz_certificat/…", a link): this module then decides the
   *   access.
   */
  public function concerns(string $uri): bool {
    if (!str_starts_with($uri, 'private://')) {
      return FALSE;
    }
    $private = $this->streamWrapperManager->isValidScheme('private');
    $normalized = $private ? $this->streamWrapperManager->normalizeUri($uri) : $uri;
    if (str_starts_with($uri, static::FOLDER) || str_starts_with($normalized, static::FOLDER)) {
      return TRUE;
    }
    // The file itself, when another path leads to it.
    return $private && $this->isStoredInTheFolder($normalized);
  }

  /**
   * Tells whether a file is stored in the folder, whatever the path to it.
   *
   * The directories above the file are compared to the folder by their
   * identity on the file system, not by their name: a ".." segment, a link,
   * or another case of the name on a file system that ignores it, all lead to
   * the same directory.
   *
   * @param string $uri
   *   The URI of a private file.
   *
   * @return bool
   *   TRUE when the file exists and one of its parent directories is the
   *   folder of the certificates.
   */
  protected function isStoredInTheFolder(string $uri): bool {
    $folder = $this->fileSystem->realpath(rtrim(static::FOLDER, '/'));
    $path = $this->fileSystem->realpath($uri);
    $expected = $folder ? @stat($folder) : FALSE;
    $found = FALSE;
    while ($expected && $path && !$found && dirname($path) !== $path) {
      $path = dirname($path);
      $stat = @stat($path);
      $found = $stat && $stat['ino'] > 0
        ? [$stat['dev'], $stat['ino']] === [$expected['dev'], $expected['ino']]
        : strcasecmp($path, $folder) === 0;
    }
    return $found;
  }

  /**
   * Tells whether an account downloads a file of the folder.
   *
   * @param string $uri
   *   The URI of a file the folder of the certificates is concerned by, see
   *   concerns().
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return bool
   *   TRUE for the owner of the certificate and for an account with the
   *   "administer site configuration" permission. Always FALSE for an
   *   anonymous user, and for a URI that is not the one of a link to a
   *   certificate: a ".." segment, another path that leads to the folder.
   */
  public function isGranted(string $uri, AccountInterface $account): bool {
    $is_link = str_starts_with($uri, static::FOLDER)
      && !in_array('..', preg_split('@[/\\\\]@', $uri), TRUE);
    if (!$is_link || $account->isAnonymous()) {
      return FALSE;
    }
    return $account->hasPermission(static::PERMISSION) || $this->isOwner($uri, $account);
  }

  /**
   * Tells whether a certificate is recorded for an account.
   *
   * @param string $uri
   *   The URI of the certificate.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return bool
   *   TRUE when the quiz history of the account holds this certificate.
   */
  protected function isOwner(string $uri, AccountInterface $account): bool {
    return (bool) $this->database->select('vactory_quiz_history', 'h')
      ->condition('h.uid', $account->id())
      ->condition('h.certificat', $uri)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
