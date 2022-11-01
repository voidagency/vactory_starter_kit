<?php

namespace Drupal\vactory_tender\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\media\Entity\Media;


/**
 *
 * Before downloading requested tender document.
 *
 * @package Drupal\vactory_render\Controller
 */

class TenderSid extends ControllerBase
{
  public function getFileUrl($sid, Request $request) {
    // Load node from submission.
    if (isset($sid) && !empty($sid)) {
      $webform_submission = \Drupal\webform\Entity\WebformSubmission::load($sid);
      if (isset($webform_submission)){
        $data = $webform_submission->getData();
        $node =  \Drupal::entityTypeManager()->getStorage('node')->load($data['tender']);
        $mid = $node->get('field_vactory_media_file')
          ->getValue()[0]['target_id'];
        $media = Media::load($mid);
        $fid = $media->getSource()->getSourceFieldValue($media);
        $file = File::load($fid);
        $url = \Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl();
        return new JsonResponse($url, 200);
      }
      else {
        $response['message'] = $this->t("Error: No submission with id : @sid", ['@sid' => $sid]);
        return new JsonResponse($response, 400);
      }
    }
  }
}
