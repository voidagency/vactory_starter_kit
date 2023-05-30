<?php

namespace Drupal\vactory_private_files_decoupled;

use DateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;
use Drupal\vactory_private_files_decoupled\Access\PrivateFileTokenGenerator;

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
    $now = new DateTime();
    $this->currentTimeStamp = $now->getTimestamp();
  }

  /**
   * Generate private file url.
   */
  public function generatePrivateFileUrl(FileInterface $file) {
    $uri = $file->downloadUrl();
    $query = $uri->getOptions()['query'];
    $sessionId = $this->privateFileTokenGenerator->get($uri->toString(), $this->currentTimeStamp);
    $query['sessionId'] = $sessionId;
    $query['timestamp'] = $this->currentTimeStamp;
    $uri->setOption('query', $query);
    return [
      '_default' => $uri->toString(),
      'extension' => $file->getMimeType(),
      'fid' => $file->id(),
      'file_name' => $file->label(),
    ];
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
