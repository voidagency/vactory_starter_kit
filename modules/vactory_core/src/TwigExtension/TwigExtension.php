<?php

namespace Drupal\vactory_core\TwigExtension;

use Drupal\Core\Template\Attribute;
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
    $block = \Drupal\block\Entity\Block::load($delta);
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
    $file = \Drupal\file\Entity\File::load($fid);
    if (isset($file)) {
      return $file;
    }

    return FALSE;
  }

}
