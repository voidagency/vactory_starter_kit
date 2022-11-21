<?php

namespace Drupal\vactory_private_files_decoupled\Controller;

use DateTime;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\vactory_private_files_decoupled\Access\PrivateFileTokenGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Class private files controller.
 */
class PrivateFilesController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var int
   */
  protected $currentTimeStamp;

  /**
   * Private File Token Generator.
   *
   * @var \Drupal\vactory_private_files_decoupled\Access\PrivateFileTokenGenerator
   */
  protected $privateFileTokenGenerator;

  /**
   * Constructs document controller.
   */
  public function __construct(AccountProxyInterface $accountProxy, EntityTypeManagerInterface $entityTypeManager, FileUrlGeneratorInterface $fileUrlGenerator, PrivateFileTokenGenerator $privateFileTokenGenerator) {
    $this->currentUser = $accountProxy->getAccount();
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $now = new DateTime();
    $this->currentTimeStamp = $now->getTimestamp();
    $this->privateFileTokenGenerator = $privateFileTokenGenerator;
  }

  /**
   * Create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
      $container->get('vactory_private_files_decoupled.access')
    );
  }

  /**
   * Get user documents.
   */
  public function getUserPrivateFiles(Request $request) {
    $files = $this->entityTypeManager->getStorage('file')
      ->loadByProperties(['uid' => $this->currentUser->id()]);
    if ($files == []) {
      return [];
    }

    $results = $this->getPrivateFiles($files);

    return new JsonResponse([
      'files' => $results,
      'count' => count($results),
    ], Response::HTTP_OK);

  }

  /**
   * Get Private Files From Fids.
   */
  public function generateUrlForPrivateFileFromFids(Request $request) {
    $fids = $request->query->get('files', []);
    if ($fids == []) {
      return [];
    }
    $fids = explode(',', $fids);
    if ($this->currentUser->isAuthenticated()) {
      $fids = \Drupal::entityQuery('file')
        ->condition('uid', $this->currentUser->id())
        ->condition('fid', $fids, 'IN')
        ->execute();
    }

    $files = File::loadMultiple($fids);
    if (!isset($files) || empty($files)) {
      return [];
    }

    $results = $this->getPrivateFiles($files);
    return new JsonResponse([
      'files' => $results,
    ], Response::HTTP_OK);
  }

  public function getPrivateFiles($files) {
    $results = [];
    foreach ($files as $file) {
      if ($this->currentUser->isAuthenticated()) {
        $uri = $file->downloadUrl();
        $query = $uri->getOptions()['query'];
        $sessionId = $this->privateFileTokenGenerator->get($uri->toString(), $this->currentTimeStamp);
        $query['sessionId'] = $sessionId;
        $query['timestamp'] = $this->currentTimeStamp;
        $uri->setOption('query', $query);
        $results[] = [
          '_default' => $uri->toString(),
          'extension' => $file->getMimeType(),
          'fid' => $file->id(),
          'file_name' => $file->label(),
        ];
      }
      else {
        $results[] = [
          'fid' => $file->id(),
          'file_name' => $file->label(),
        ];
      }

    }
    return $results;
  }

}
