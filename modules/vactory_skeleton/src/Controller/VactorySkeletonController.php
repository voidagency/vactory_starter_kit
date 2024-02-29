<?php

namespace Drupal\vactory_skeleton\Controller;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The VactorySkeletonController class.
 */
class VactorySkeletonController extends ControllerBase {

  /**
   * Get nodes skeletons.
   */
  public function index() {

    $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple();
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    $result = [];
    foreach ($nodes as $node) {
      $concernedNodeTranslation = $node;
      if ($node->hasTranslation($langcode)) {
        $concernedNodeTranslation = $node->getTranslation($langcode);
      }

      $mid = $concernedNodeTranslation->get('node_skeleton_image')->target_id;
      if (!isset($mid)) {
        $result[$node->bundle()][$node->id()] = '';
        continue;
      }

      $media = Media::load($mid);
      if (!$media instanceof MediaInterface && !$media->hasField('field_media_image')) {
        $result[$node->bundle()][$node->id()] = '';
        continue;
      }

      $fid = $media->get('field_media_image')->target_id;
      $file = $fid ? File::load($fid) : NULL;
      if (!$file instanceof FileInterface) {
        $result[$node->bundle()][$node->id()] = '';
        continue;
      }

      $image_uri = $file->getFileUri();
      $url = \Drupal::service('vacory_decoupled.media_file_manager')->getMediaAbsoluteUrl($image_uri);
      $result[$node->bundle()][$node->id()] = $url;

    }

    return new JsonResponse($result);
  }

}
