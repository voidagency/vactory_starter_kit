<?php

namespace Drupal\vactory_cloudinary\StreamWrapper;

use Cloudinary\Asset\Media;
use Drupal\cloudinary_stream_wrapper\StreamWrapper\CloudinaryStreamWrapper;

/**
 * Cloudinary stream wrapper decorator class.
 */
class CloudinaryStreamWrapperOverride extends CloudinaryStreamWrapper {

  protected $cloudinaryStreamWrapper;

  /**
   * Object constructor.
   *
   * Load Cloudinary PHP SDK & initialize Cloudinary configuration.
   */
  public function __construct(CloudinaryStreamWrapper $cloudinaryStreamWrapper) {
    $this->cloudinaryStreamWrapper = $cloudinaryStreamWrapper;
    parent::__construct();
  }

  /**
   * Returns a web accessible URL for the resource.
   *
   * @return string
   *   A web accessible URL for the resource.
   */
  public function getExternalUrl() {
    $target = $this->getTarget();

    // Check if the uri contains raw transformations where we previously fixed
    // the version of the asset.
    if (preg_match('/^(.+)\/v1\/(.+)\.(.+)$/', $target, $matches)) {
      $options['secure'] = TRUE;
      $options['raw_transformation'] = $matches[1];
      $source = "{$matches[2]}.{$matches[3]}";

      return Media::fromParams($source, $options)->toUrl();
    }

    $resource = $this->loadResource($this->uri);

    if (!$resource) {
      \Drupal::logger('cloudinary_stream_wrapper')->error('Failed to get external URL for %uri', ['%uri' => $this->uri]);
      $uri = $this->getTarget($this->uri);
      // Handle video resource type case.
      $params = [];
      if (in_array(pathinfo($uri, PATHINFO_EXTENSION), ['mp4', 'avi', 'flv', 'wmv', 'mov'])) {
        $params = ['resource_type' => 'video'];
      }
      return Media::fromParams($uri, $params)->toUrl();
    }

    return $resource['secure_url'];
  }

}
