<?php

namespace Drupal\vactory_dynamic_field_video_help\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Provide an URL form element with link attributes.
 *
 * @FormElement("media_video")
 */
class MediaVideoElement extends FormElement {

  /**
   * Returns the element properties for this element.
   *
   * @return array
   *   An array of element properties. See
   *   \Drupal\Core\Render\ElementInfoManagerInterface::getInfo() for
   *   documentation of the standard properties of all elements, and the
   *   return value format.
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processMediaVideo'],
      ],
      '#element_validate' => [
        [$class, 'validateMediaVideo'],
      ],
      '#theme_wrappers' => ['fieldset'],
      '#multiple' => FALSE,
    ];
  }

  /**
   * Media video form element process callback.
   */
  public static function processMediaVideo(&$element, FormStateInterface $form_state, &$form) {
    $video_value = $form_state->getValue($element['#parents'])["id"];
    $element['video'] = [
      '#type' => 'media_library',
      '#title' => t('Video'),
      '#allowed_bundles' => ['remote_cloudinary_video'],
      '#required' => TRUE,
      '#default_value' => (isset($video_value) && !empty($video_value)) ?
              $video_value : NULL
    ];

    return $element;
  }

  /**
   * Media video form element validate callback.
   */
  public static function validateMediaVideo(&$element, FormStateInterface $form_state, &$form) {
    $values = $form_state->getValue($element['#parents'])["video"];
    $mid = $values;
    if (isset($mid) && !empty($mid)) {
      $media = Media::load($mid);
      if (isset($media) && !empty($media)) {
        $fid = $media->field_cloudinary_video->target_id;
        $file = File::load($fid);
        $file->setPermanent();
        $file->save();
        $url = \Drupal::service('file_url_generator')->transformRelative(\Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl());
        $video = [
          'id' => $mid,
          'url' => $url,
        ];
        $values = $video;
      }
    }
    $form_state->setValue($element['#parents'], ($values));
  }

}
