<?php

namespace Drupal\vactory_core\Plugin\TwigPlugin;

use Drupal\media\Entity\Media;
use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * Image plugin.
 *
 * @TwigPlugin(
 *   id = "twig_extender_vactory_image_url",
 *   label = @Translation("Image twig plugin"),
 *   type = "function",
 *   name = "get_image_url",
 *   function = "getImageUrl"
 * )
 */
class VactoryImageURL extends TwigPluginBase {

  /**
   * Implement get image function.
   */
  public function getImageUrl($fid = 0) {
    $file = Media::load($fid);

    if (!$file) {
      return;
    }

    $path = file_create_url($file->thumbnail->entity->getFileUri());

    return $path;
  }

}
