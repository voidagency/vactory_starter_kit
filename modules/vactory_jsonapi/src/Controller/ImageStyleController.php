<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Entity\EntityStorageException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ImageStyleController extends ControllerBase
{

  /**
   * Try and find an image style that matches the requested dimensions.
   *
   * @param array $requested_dimensions
   *   The calculated requested dimensions.
   *
   * @return mixed
   *   A matching image style or NULL if none was found.
   */
  public function findImageStyle(array $requested_dimensions)
  {
    // Try and get an exact match:
    $name = 'decoupled_image_' . $requested_dimensions[0] . '_' . $requested_dimensions[1];
    $image_style = ImageStyle::load($name);

    // No usable image style could be found, so we will have to create one.
    if (empty($image_style)) {
      $image_style = $this->createDecoupledimageStyle($requested_dimensions);
    }

    return $image_style;
  }

  /**
   * Create an image style from the requested dimensions.
   *
   * @param array $requested_dimensions
   *   The array containing the dimensions.
   *
   * @return mixed
   *   The image style or FALSE in case something went wrong.
   */
  public function createDecoupledimageStyle(array $requested_dimensions)
  {
    $name = 'decoupled_image_' . $requested_dimensions[0] . '_' . $requested_dimensions[1];
    $label = 'Decoupled Image (' . $requested_dimensions[0] . 'x' . $requested_dimensions[1] . ')';

    // When multiple images width the same dimension are requested in 1 page
    // we can sometimes trigger errors here. Image style can already be
    // created by another request that came in a few milliseconds before this
    // request. Catch that error and try and use the image style that was
    // already created.
    try {
      $style = ImageStyle::create(['name' => $name, 'label' => $label]);
      $configuration = [
        'uuid' => NULL,
        'weight' => 0,
        'data' => [
          'upscale' => FALSE,
          'width' => NULL,
          'height' => NULL,
        ],
      ];
      $configuration['data']['width'] = $requested_dimensions[0];
      if ($requested_dimensions[1] > 0) {
        $configuration['data']['height'] = $requested_dimensions[1];
      }

      // Height is NULL by default, images are scaled.
      if ($configuration['data']['width'] == NULL || $configuration['data']['height'] == NULL) {
        $configuration['id'] = 'image_scale';
      } else {
        $configuration['id'] = 'image_scale_and_crop';
      }

      $effect = \Drupal::service('plugin.manager.image.effect')
        ->createInstance($configuration['id'], $configuration);
      $style->addImageEffect($effect->getConfiguration());
      $style->save();
      $styles[$name] = $style;
      $image_style = $styles[$name];
    } catch (EntityStorageException $e) {
      $image_style = ImageStyle::load($name);
    } catch (\Exception $e) {
      return NULL;
    }

    return $image_style;
  }

  /**
   * Deliver an image from the requested parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The transferred file as response or some error response.
   */
  public function image(Request $request)
  {
    $width = $request->query->get('width');
    $height = $request->query->get('height');

    $error_msg = '';
    // Bail out if the arguments are not numbers.
    if (!is_numeric($width) || !is_numeric($height)) {
      $error_msg = 'Error generating image, invalid parameters.';
    }

    // Try and find a matching image style.
    $requested_dimensions = [0 => $width, 1 => $height];
    $image_style = $this->findImageStyle($requested_dimensions);
    if (empty($image_style)) {
      $error_msg = 'Error generating image, Could not find matching image style.';
    }

    // Error handling.
    if (!empty($error_msg)) {
      return new JsonResponse([
        'status' => FALSE,
        'message' => $error_msg
      ], 500);
    }

    return new JsonResponse([
      'status' => TRUE,
    ], 200);
  }

}
