<?php

namespace Drupal\vactory_core\Plugin\TwigPlugin;

use Drupal\media\Entity\Media;
use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * Image plugin.
 *
 * @TwigPlugin(
 *   id = "twig_extender_vactory_image",
 *   label = @Translation("Image twig plugin"),
 *   type = "function",
 *   name = "get_image",
 *   function = "getImage"
 * )
 */
class VactoryImage extends TwigPluginBase {

  /**
   * Implement get image function.
   */
  public function getImage($fid = 0) {
    $file = Media::load($fid);

    if (!$file) {
      return;
    }

    $path = $file->thumbnail->entity->getFileUri();

    return $path;
  }

}
