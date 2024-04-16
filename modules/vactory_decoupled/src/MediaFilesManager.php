<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Decoupled media file manager.
 */
class MediaFilesManager {

  /**
   * File url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructor.
   */
  public function __construct(FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * Get file absolute url.
   */
  public function getMediaAbsoluteUrl($uri) {
    $url = $this->fileUrlGenerator->generateAbsoluteString($uri);
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    if (strpos($url, $base_url) === 0) {
      if ($base_media_url = Settings::get('BASE_MEDIA_URL', '')) {
        $relative_path = $this->fileUrlGenerator->generateString($uri);
        $url = $base_media_url . $relative_path;
      }
    }
    return $url;
  }

  /**
   * Convert to media absolute url.
   */
  public function convertToMediaAbsoluteUrl($url) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    if (strpos($url, $base_url) === 0) {
      $url_info = parse_url($url);
      $path = $url_info['path'] ?? '';
      $query = $url_info['query'] ?? '';
      if (!empty($path)) {
        if ($base_media_url = Settings::get('BASE_MEDIA_URL', '')) {
          $query = !empty($query) ? "?{$query}" : $query;
          $path = "{$path}{$query}";
          $url = $base_media_url . $path;
        }
      }
    }
    return $url;
  }

  /**
   * Get file image styles.
   */
  public function getFileImageStyles($entity) {
    $config = \Drupal::config('jsonapi_image_styles.settings');
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
        if ($style instanceof ImageStyle) {
          $uris[$name] = $this->getMediaAbsoluteUrl($style->buildUrl($uri));
        }
      }
    }
    return $uris;
  }

}
