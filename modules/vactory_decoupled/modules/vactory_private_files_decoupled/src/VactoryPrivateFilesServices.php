<?php

namespace Drupal\vactory_private_files_decoupled;

use DateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;
use Drupal\vactory_private_files_decoupled\Access\PrivateFileTokenGenerator;
use Drupal\Component\Utility\UrlHelper;

/**
 * Vactory private files service.
 */
class VactoryPrivateFilesServices {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Private File Token Generator.
   *
   * @var \Drupal\vactory_private_files_decoupled\Access\PrivateFileTokenGenerator
   */
  protected $privateFileTokenGenerator;

  /**
   * @var int
   */
  protected $currentTimeStamp;


  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $accountProxy, PrivateFileTokenGenerator $privateFileTokenGenerator) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $accountProxy->getAccount();
    $this->privateFileTokenGenerator = $privateFileTokenGenerator;
    $this->currentTimeStamp = \Drupal::time()->getRequestTime();
  }

  /**
   * Generate private file url.
   */
  public function generatePrivateFileUrl(FileInterface $file) {
    $uri = $file->getFileUri();
    $download_url = $file->downloadUrl()->toString();
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    $formatedFile = [
      '_default' => $download_url,
      'extension' => $file->getMimeType(),
      'fid' => $file->id(),
      'file_name' => $file->label(),
    ];
    if ($stream_wrapper_manager::getScheme($uri) == 'private') {
      $token_query = [
        'sessionId' => $this->privateFileTokenGenerator->get($download_url, $this->currentTimeStamp),
        'timestamp' => $this->currentTimeStamp,
      ];
      $download_url .= (strpos($download_url, '?') !== FALSE ? '&' : '?') . UrlHelper::buildQuery($token_query);
      $formatedFile['_default'] = $download_url;
    }
    return $formatedFile;
  }

  /**
   * Generate privates files urls.
   */
  public function generatePrivateFilesUrls(array $files = []) {
    $results = [];
    foreach ($files as $file) {
      $results[] = $this->generatePrivateFileUrl($file);
    }
    return $results;
  }

}
