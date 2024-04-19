<?php

namespace Drupal\vactory_decoupled_image_styles;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\vactory_decoupled\MediaFilesManager;

/**
 * Decoupled image styles service.
 */
class ImageStylesService {

  /**
   * Media Files Manager.
   *
   * @var \Drupal\vactory_decoupled\MediaFilesManager
   */
  protected $mediaFilesManager;

  /**
   * Constructor.
   */
  public function __construct(MediaFilesManager $mediaFilesManager) {
    $this->mediaFilesManager = $mediaFilesManager;
  }

  /**
   * Get file image styles.
   */
  public function getFileImageStyles($entity) {
    $config = \Drupal::config('jsonapi_image_styles.settings');
    $exposed_image_styles = \Drupal::config('vactory_decoupled_image_styles.settings')->get('image_styles');
    $styles = [];

    $uri = ($entity instanceof File && substr($entity->getMimeType(), 0, 5) === 'image') ? $entity->getFileUri() : FALSE;

    if ($uri) {
      $defined_styles = $config->get('image_styles') ?? [];
      if (!empty(array_filter($defined_styles))) {
        foreach ($defined_styles as $key) {
          $styles[$key] = ImageStyle::load($key);
        }
      }
      else {
        $styles = ImageStyle::loadMultiple();
      }

      $uris = [];
      foreach ($styles as $name => $style) {
        if ($style instanceof ImageStyle && $exposed_image_styles[$name] !== 0) {
          $uris[$name] = $this->mediaFilesManager->getMediaAbsoluteUrl($style->buildUrl($uri));
        }
      }
    }
    return $uris;
  }

}
