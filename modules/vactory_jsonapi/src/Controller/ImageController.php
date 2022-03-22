<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Entity\EntityStorageException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends ControllerBase
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
      // When the site starts from a cold cache situation and a lot of requests
      // come in, the webserver might fail at this point, so try a few times.
      $counter = 0;
      while (empty($image_style) && $counter < 10) {
        usleep(rand(10000, 50000));
        $image_style = $this->createDecoupledimageStyle($requested_dimensions);
        $counter++;
      }
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
      // Wait a tiny little bit to make sure another request isn't still adding
      // effects to the image style.
      usleep(rand(10000, 50000));
      $image_style = ImageStyle::load($name);
    } catch (Exception $e) {
      return NULL;
    }

    return $image_style;
  }

  /**
   * Deliver an image from the requested parameters.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $cached_uri
   *    The cached version of this file
   * @param int $width
   *    The requested width in pixels that came from the JS.
   * @param int $height
   *    The requested height in pixels that came from the JS.
   * @param int $fid
   *    The file id to render.
   * @param string $filename
   *    The filename, only here for SEO purposes.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The transferred file as response or some error response.
   *
   */
  public function image(Request $request, $cached_uri = '', $width, $height, $fid, $filename)
  {

    // Uncomment to test the loading effect:
//    sleep(8);

    // Cached version.
    if (!empty($cached_uri) && $decoded_uri = base64_decode($cached_uri)) {
      if (file_exists($decoded_uri)) {
        $headers = [
          'Cache-Control' => 'max-age=31536000',
        ];
        return new BinaryFileResponse($decoded_uri, 200, $headers);
      }
    }

    $error_msg = '';
    // Bail out if the arguments are not numbers.
    if (!is_numeric($width) || !is_numeric($height) || !is_numeric($fid)) {
      $error_msg = 'Error generating image, invalid parameters.';
    }

    $file = File::load($fid);

    // File not found.
    if (!$file) {
      $error_msg = 'Error generating image, file not found.';
    }

    // Filename don't match.
    if ($filename !== $file->getFilename()) {
      $error_msg = 'Error generating image, invalid filename parameters.';
    }

    // Try and find a matching image style.
    $requested_dimensions = [0 => $width, 1 => $height];
    $image_style = $this->findImageStyle($requested_dimensions);
    if (empty($image_style)) {
      $error_msg = 'Error generating image, Could not find matching image style.';
      // @todo: fallback to original image.
    }

    // Error handling.
    if (!empty($error_msg)) {
      return new Response($error_msg, 500);
    }

    $original_path = $file->getFileUri();
    $derivative_uri = $image_style->buildUri($original_path);

    // Create derivative if necessary.
    if (!file_exists($derivative_uri)) {
      $image_style->createDerivative($original_path, $derivative_uri);
    }

    // Headers.
    $image_factory = \Drupal::service('image.factory');
    $image = $image_factory->get($derivative_uri);
    $headers = [
      'Cache-Control' => 'max-age=31536000',
      'Content-Type' => $image->getMimeType(),
      'Content-Length' => $image
        ->getFileSize(),
    ];

    return new BinaryFileResponse($derivative_uri, 200, $headers);
  }

}
