<?php

namespace Drupal\vactory_core\TwigExtension;

use Drupal\block\Entity\Block;
use Drupal\Core\Template\Attribute;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\vactory_core\Vactory;

/**
 * Class TwigExtension.
 *
 * @package Drupal\vactory_core\TwigExtension
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritDoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('addAttributes',
        [$this, 'addAttributes'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('hideLabel',
        [$this, 'hideLabel'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('noWrapper',
        [$this, 'noWrapper'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('drupal_block_delta',
        [$this, 'drupalBlockDelta'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('vactory_render',
        [$this, 'vactoryRender'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('file_object',
        [$this, 'fileObject'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('is_notifications_enabled',
        [$this, 'isNotificationsEnabled'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('get_media',
        [$this, 'getMedia'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('get_image_info',
        [$this, 'getImageInfo'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('successive_image_styles',
        [$this, 'successiveImageStyles'],
        ['is_safe' => ['html']]),
    ];
  }

  /**
   * Add attributes to field.
   *
   * @usage: {{ addAttributes(content.field_vactory_image, {'class':
   *   ['vf-card__image', 'img-responsive']}) }}
   *
   * @param $field
   * @param array $attributes
   *
   * @return mixed
   */
  public function addAttributes($field, $attributes = []) {
    if (isset($field['#items']) && !empty($field['#items'])) {
      foreach ($field['#items'] as $key => $value) {
        if (isset($field[$key]['#type'])) {

          if (!isset($field[$key]['#attributes'])) {
            $field[$key]['#attributes'] = [];
          }
          $field[$key]['#attributes'] = array_merge($field[$key]['#attributes'], $attributes);
        }

        if (isset($field[$key]['#item_attributes'])) {

          $field[$key]['#item_attributes'] = array_merge($field[$key]['#item_attributes'], $attributes);
        }
      }
    }

    if (isset($field['#attributes']) && $field['#attributes'] instanceof Attribute) {
      foreach ($attributes as $key => $value) {
        $field['#attributes']->setAttribute($key, $value);
      }
    }
    else {
      $field['#attributes'] = new Attribute($attributes);
    }

    return $field;
  }

  /**
   * Hide field label.
   *
   * @usage: {{ hideLabel(content.field_vactory_image) }}
   *
   * @param $field
   *
   * @return mixed
   */
  public function hideLabel($field) {
    $field['#label_display'] = 'hidden';
    $field['#label_hidden'] = TRUE;

    return $field;
  }

  /**
   * Remove field wrapper.
   *
   * @usage: {{ noWrapper(content.field_vactory_taxonomy1) }}
   *
   * @param $field
   *
   * @return mixed
   */
  public function noWrapper($field) {
    // @see vactory_theme_suggestions_field_alter();
    $field['#vactory_no_wrapper'] = TRUE;

    return $field;
  }

  /**
   * Render block by delta.
   *
   * the block must be instanced for getting block_delta
   *
   * @param $delta
   *
   * @return string|false
   */
  public function drupalBlockDelta($delta) {
    $block = Block::load($delta);
    if ($block) {
      $variables = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($block);

      if ($variables) {
        return render($variables);
      }
    }
    return FALSE;
  }

  /**
   * Return HTML output on a certain object.
   *
   * @param string $type
   *   Type of the object: block, views, menu, form, entity.
   * @param string $object
   *   Object to retrieve.
   * @param array|string $configuration
   *   Optional params.
   *
   * @return array|\Drupal\vactory_core\html
   *   HTML output.
   */
  public function vactoryRender($type, $object, $configuration = NULL, $view_mode = NULL) {

    switch ($type) {
      case 'block':
        return Vactory::renderBlock($object, (is_array($configuration)) ? $configuration : []);

      case 'views':
        return Vactory::renderView($object, $configuration);

      case 'menu':
        return Vactory::renderMenu($object);

      case 'form':
        return Vactory::renderForm($object, $configuration);

      case 'entity':
        return Vactory::renderEntity($object, $configuration, $view_mode);
    }
  }

  /**
   * Get file object from file ID $fid in a template file.
   *
   * @param $fid
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|null|static
   */
  public function fileObject($fid) {
    $file = File::load($fid);
    if (isset($file)) {
      return $file;
    }

    return FALSE;
  }

  /**
   * Check either the vactory notifications module is enabled or not.
   *
   * @return boolean
   */
  public function isNotificationsEnabled() {
    $moduleHandler = \Drupal::service('module_handler');
    return $moduleHandler->moduleExists('vactory_notifications');
  }

  /**
   * Load media twig extension callback.
   *
   * @usage: {% set media = get_media(mid) %}
   *
   * @param $mid
   *   The media ID.
   *
   * @return \Drupal\media\Entity\Media|NULL
   */
  public function getMedia($mid) {
    $media = \Drupal::service('entity_type.manager')->getStorage('media')
      ->load($mid);
    return $media;
  }

  /**
   * Get Image Info twig extension callback.
   *
   * @usage: {% set media = get_image_info(fid) %}
   *
   * @param $image_id
   *   The image file ID.
   *
   * @return array|NULL
   */
  public function getImageInfo($image_id) {
    $image =  File::load($image_id);
    if ($image) {
      $image_meta_data = $image->getAllMetaData();
      $file_info = [
        'fid' => $image->id(),
        'uuid' => $image->get('uuid')->value,
        'uid' => $image->get('uid')->target_id,
        'created' => $image->get('created')->value,
        'status' => $image->get('status')->value,
        'type' => $image->get('type')->target_id,
        'filename' => $image->label(),
        'filemime' => $image->get('filemime')->value,
        'uri' => $image->get('uri')->value,
        'url' => file_create_url($image->get('uri')->value),
        'alt' => !empty($image->get('field_image_alt_text')->value) ? $image->get('field_image_alt_text')->value : '',
        'title' => !empty($image->get('field_image_title_text')->value) ? $image->get('field_image_title_text')->value : '',
      ];
      return array_merge($file_info, $image_meta_data);
    }
    return NULL;
  }

  /**
   * Apply successive image styles twig extension callback.
   *
   * @param $image_uri
   *   The image uri.
   * @param $styles
   *   Ordered list of styles to apply.
   *
   * @return string|null
   */
  public function successiveImageStyles($image_uri, $styles) {
    if (empty($styles)) {
      return NULL;
    }
    $image_style = ImageStyle::load(array_values($styles)[0]);
    if ($image_style) {
      unset($styles[array_keys($styles)[0]]);
      $destination_uri = $image_style->buildUri($image_uri);
      $image_style->createDerivative($image_uri, $destination_uri);
      return empty($styles) ? $image_style->buildUrl($destination_uri) : $this->successiveImageStyles($destination_uri, $styles) ;
    }
    return NULL;
  }

}
