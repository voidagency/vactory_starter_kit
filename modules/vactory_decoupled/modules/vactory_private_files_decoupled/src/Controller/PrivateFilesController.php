<?php

namespace Drupal\vactory_private_files_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\vactory_private_files_decoupled\VactoryPrivateFilesServices;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * Account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Vactory privates files services.
   *
   * @var \Drupal\vactory_private_files_decoupled\VactoryPrivateFilesServices
   */
  protected $vactoryPrivateFilesServices;

  /**
   * Constructs document controller.
   */
  public function __construct(AccountProxyInterface $accountProxy, EntityTypeManagerInterface $entityTypeManager, VactoryPrivateFilesServices $vactoryPrivateFilesServices) {
    $this->currentUser = $accountProxy->getAccount();
    $this->entityTypeManager = $entityTypeManager;
    $this->vactoryPrivateFilesServices = $vactoryPrivateFilesServices;
  }

  /**
   * Create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
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

    $results = $this->vactoryPrivateFilesServices->generatePrivateFilesUrls($files);

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

    $results = $this->vactoryPrivateFilesServices->generatePrivateFilesUrls($files);
    return new JsonResponse([
      'files' => $results,
    ], Response::HTTP_OK);
  }

}
