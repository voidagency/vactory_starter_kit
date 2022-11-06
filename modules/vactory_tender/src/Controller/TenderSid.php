<?php

namespace Drupal\vactory_tender\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\media\Entity\Media;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\vactory_core\Services\VactoryDevTools;


/**
 *
 * Get the path of the file to download.
 *
 * @package Drupal\vactory_render\Controller
 */

class TenderSid extends ControllerBase
{
  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Stream wrapper manager service.
   *
   * @var StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Vactory devtools service.
   *
   * @var \Drupal\vactory_core\Services\VactoryDevTools
   */
  protected $vactoryDevTools;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    StreamWrapperManagerInterface $streamWrapperManager,
    VactoryDevTools $vactoryDevTools
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->vactoryDevTools = $vactoryDevTools;
  }

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('stream_wrapper_manager'),
      $container->get('vactory_core.tools')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFileUrl($sid, Request $request) {
    try {
      $decrypted_sid = $this->vactoryDevTools->decrypt($sid);

      if ($decrypted_sid == null) {
        $response['message'] = $this->t("Error: Not a valid sid");
        return new JsonResponse($response, 400);
      }
        $decrypted_sid = str_replace('vactory_tender', '', $decrypted_sid);

        // Load node from submission.
        $webform_submission = WebformSubmission::load($decrypted_sid);

        // Check if the submissions owner is the current user
        $current_user = \Drupal::currentUser()->id();
        if (strval($current_user) !== $webform_submission->getOwnerID()) {
          $response['message'] = $this->t("Error: Current user is not the submission's owner");
          return new JsonResponse($response, 400);
        }
        if (!isset($webform_submission)) {
          $response['message'] = $this->t("Error: No submission with id : @sid", ['@sid' => $sid]);
          return new JsonResponse($response, 400);
        }
        $data = $webform_submission->getData();

        $node =  $this-> entityTypeManager->getStorage('node')->load($data['tender']);
        $mid = $node->get('field_vactory_media_file')
        ->getValue()[0]['target_id'];
        $media = Media::load($mid);
        $fid = $media->getSource()->getSourceFieldValue($media);
        $file = File::load($fid);
        if ($file) {
          $url = $this->streamWrapperManager->getViaUri($file->getFileUri())->getExternalUrl();
          return new JsonResponse($url, 200);
        } else {
          $response['message'] = $this->t("Error: Missing File");
          return new JsonResponse($response, 400);
        }
    }
    catch(\Exception $e) {
      $response['message'] = $this->t("Error: Exception");
      return new JsonResponse($response, 400);
    }
  }
}
