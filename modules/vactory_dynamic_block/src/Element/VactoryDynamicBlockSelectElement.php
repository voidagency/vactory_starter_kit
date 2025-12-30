<?php

namespace Drupal\vactory_dynamic_block\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an autocomplete form element for selecting Dynamic Blocks.
 *
 * @FormElement("vactory_dynamic_block_select")
 */
class VactoryDynamicBlockSelectElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#default_value' => '',
      '#process' => [[$class, 'processElement']],
      '#element_validate' => [[$class, 'validateElement']],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Element process callback.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;

    // Get default value (block ID) - handle both scalar and array formats.
    $default_value = $element['#default_value'] ?? '';
    $default_entity = NULL;

    // Extract block_id from various possible formats.
    if (is_array($default_value)) {
      // Handle nested structure: ['block' => ['block_id' => '1']].
      if (isset($default_value['block']['block_id'])) {
        $default_value = $default_value['block']['block_id'];
      }
      // Handle flat structure: ['block_id' => '1'].
      elseif (isset($default_value['block_id'])) {
        $default_value = $default_value['block_id'];
      }
      else {
        $default_value = '';
      }
    }

    if (!empty($default_value)) {
      $default_entity = \Drupal::entityTypeManager()
        ->getStorage('vactory_dynamic_block')
        ->load($default_value);
    }

    $element['block_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $element['#title'] ?? t('Dynamic Block'),
      '#target_type' => 'vactory_dynamic_block',
      '#default_value' => $default_entity,
      '#required' => $element['#required'] ?? FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // Validation if needed.
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && isset($input['block_id'])) {
      return $input['block_id'];
    }
    return $element['#default_value'] ?? '';
  }

}
