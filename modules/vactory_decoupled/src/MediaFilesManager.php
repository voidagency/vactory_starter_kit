<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

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
   * Get media absolute url by Mid.
   */
  public function getMediaAbsoluteUrlByMid($mid, $withMeta = FALSE) {
    $media = Media::load($mid);
    if (!$media instanceof Media) {
      return NULL;
    }

    $fid = $media->get('field_media_image')->target_id;
    if (!is_numeric($fid)) {
      return NULL;
    }

    $file = File::load($fid);
    if (!$file instanceof File) {
      return NULL;
    }

    $uri = $file->getFileUri();
    $url = $this->getMediaAbsoluteUrl($uri);

    if (!$withMeta) {
      return $url;
    }
    else {
      return [
        'src' => $url,
        'meta' => $media->get('field_media_image')
          ->first()
          ->getValue(),
      ];
    }
  }

}
