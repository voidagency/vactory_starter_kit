<?php

namespace Drupal\vactory_color_picker\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an HTML5 color picker form element.
 *
 * @FormElement("vactory_color_picker")
 */
class VactoryColorPicker extends FormElement {

  /**
   * Default display color value for the HTML5 color input.
   */
  const DEFAULT_DISPLAY_COLOR = '#FF0000';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processElement'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#default_value' => NULL,
      '#clear_button' => TRUE,
      '#clear_button_text' => 'Clear',
      '#attached' => [
        'library' => [
          'vactory_color_picker/widget',
        ],
      ],
    ];
  }

  /**
   * Process callback for the color picker element.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Generate unique ID for the element if not already set.
    if (!isset($element['#id'])) {
      $element['#id'] = Html::getUniqueID('edit-' . implode('-', $element['#parents'] ?? []));
    }

    // Get the actual value from the element.
    $actual_value = $element['#default_value'] ?? '';

    // Ensure the value is a valid hex color.
    if (!empty($actual_value) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $actual_value)) {
      $actual_value = '';
    }

    // Display value for color input (use default color when no value is set).
    $display_value = !empty($actual_value) ? $actual_value : self::DEFAULT_DISPLAY_COLOR;

    // Generate unique IDs based on element ID.
    $element_id = $element['#id'];
    $hidden_input_id = $element_id . '-hidden';
    $color_input_id = $element_id . '-input';

    // Hidden input to store the actual value (this is what gets submitted).
    // This matches exactly what the widget does.
    $element['_value'] = [
      '#type' => 'hidden',
      '#default_value' => $actual_value,
      '#attributes' => [
        'id' => $hidden_input_id,
        'class' => ['vactory-color-picker-hidden'],
      ],
      // Set parents to match main element so the value is submitted directly.
      '#parents' => $element['#parents'] ?? [],
    ];

    // Container for display elements.
    // This matches exactly what the widget does.
    $element['color_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['vactory-color-picker-wrapper'],
        'style' => 'display: flex; align-items: center; gap: 5px;',
      ],
    ];

    // HTML5 color input for display.
    $element['color_wrapper']['color_display'] = [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => [
        'type' => 'color',
        'value' => $display_value,
        'id' => $color_input_id,
        'class' => ['vactory-color-picker-input'],
        'data-hidden-input-id' => $hidden_input_id,
        'data-default-color' => self::DEFAULT_DISPLAY_COLOR,
      ],
    ];

    // Clear link (using span element to avoid form submission).
    // This matches exactly what the widget does.
    if (!empty($element['#clear_button'])) {
      $element['color_wrapper']['clear'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $element['#clear_button_text'] ?? 'Clear',
        '#attributes' => [
          'class' => ['vactory-color-picker-clear'],
          'data-color-input-id' => $color_input_id,
          'data-hidden-input-id' => $hidden_input_id,
          'data-default-color' => self::DEFAULT_DISPLAY_COLOR,
          'style' => 'padding: 2px 8px; font-size: 12px; cursor: pointer; border: 1px solid #ccc; border-radius: 3px; background: #f5f5f5; display: inline-block;',
        ],
      ];
    }

    return $element;
  }

  /**
   * Validation callback for the color picker element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   */
  public static function validateElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = $element['#value'] ?? '';

    // If color value is empty, that's valid (means no color selected).
    if (empty(trim($value))) {
      return;
    }

    // Validate using ColorAPI service, like jquery_colorpicker does.
    $color_service = \Drupal::service('colorapi.service');
    if (!$color_service->isValidHexadecimalColorString($value)) {
      $form_state->setError($element, t('@value is not a valid hexidecimal color.', ['@value' => $value]));
      return;
    }

    // Normalize the color value to uppercase.
    $form_state->setValueForElement($element, strtoupper($value));
  }

  /**
   * Value callback for the color picker element.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL && is_scalar($input)) {
      // Validate and normalize using ColorAPI service.
      $color_service = \Drupal::service('colorapi.service');
      if ($color_service->isValidHexadecimalColorString($input)) {
        return strtoupper($input);
      }
    }

    return NULL;
  }

}
