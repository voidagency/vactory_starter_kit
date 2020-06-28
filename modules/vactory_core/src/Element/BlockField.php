<?php

namespace Drupal\vactory_core\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Providers an element design for embedding iframes.
 *
 * @RenderElement("block_field")
 */
class BlockField extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#attributes' => [],
      '#theme_wrappers' => ['form_element'],
      '#pre_render' => [
        [static::class, 'preRenderBlockField'],
      ],
      '#process' => [
        'Drupal\Core\Render\Element\RenderElement::processAjaxForm',
        [static::class, 'processElement'],
      ],
    ];
  }

  /**
   * Transform the render element structure into a renderable one.
   *
   * @param array $element
   *   An element array before being processed.
   *
   * @return array
   *   The processed and renderable element.
   */
  public static function preRenderBlockField($element) {
    $element['#attributes']['type'] = 'select';
    Element::setAttributes($element, [
      'id',
      'name',
      'value',
      'size',
      'maxlength',
    ]);
    static::setAttributes($element, ['form-block_field']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $options = [];
    /** @var \Drupal\block_field\BlockFieldManagerInterface $block_field_manager */
    $block_field_manager = \Drupal::service('block_field.manager');
    $definitions = $block_field_manager->getBlockDefinitions();
    foreach ($definitions as $id => $definition) {
      // If allowed plugin ids are set then check that this block should be
      // included.
      $category = (string) $definition['category'];
      $options[$category][$id] = $definition['admin_label'];
    }

    $element['plugin_id'] = [
      '#type' => 'select',
      '#title' => t('Block'),
      '#options' => $options,
      '#empty_option' => t('- None -'),
      '#required' => $element['#required'],
      '#default_value' => isset($element['#default_value']['plugin_id']) ? $element['#default_value']['plugin_id'] : '',
    ];

    return $element;
  }

}
