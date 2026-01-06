<?php

namespace Drupal\vactory_color_picker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\colorapi\Plugin\Field\FieldWidget\ColorapiWidgetBase;

/**
 * HTML5 Color Picker widget for Color API fields.
 *
 * @FieldWidget(
 *   id = "vactory_color_picker",
 *   label = @Translation("HTML5 Color Picker"),
 *   field_types = {
 *     "colorapi_color_field"
 *   }
 * )
 */
class VactoryColorPickerWidget extends ColorapiWidgetBase {

  /**
   * Default display color value for the HTML5 color input.
   */
  const DEFAULT_DISPLAY_COLOR = '#FF0000';

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('HTML5 color picker input');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Hide the name field.
    unset($element['name']);

    // Get the actual value from the field item.
    $actual_value = isset($items[$delta]) && $items[$delta]->getHexadecimal()
      ? $items[$delta]->getHexadecimal()
      : '';

    // Ensure the value is a valid hex color.
    if (!empty($actual_value) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $actual_value)) {
      $actual_value = '';
    }

    // Display value for color input (use default color when no value is set).
    $display_value = !empty($actual_value) ? $actual_value : self::DEFAULT_DISPLAY_COLOR;

    // Generate unique IDs.
    $unique_id = md5(serialize($element['#field_parents'] ?? []) . $delta);
    $hidden_input_id = 'color-picker-hidden-' . $unique_id;
    $color_input_id = 'color-picker-input-' . $unique_id;

    // Hidden input to store the actual value (this is what gets submitted).
    $element['color'] = [
      '#type' => 'hidden',
      '#default_value' => $actual_value,
      '#attributes' => [
        'id' => $hidden_input_id,
        'class' => ['vactory-color-picker-hidden'],
      ],
    ];

    // Container for display elements.
    $element['color_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['vactory-color-picker-wrapper'],
        'style' => 'display: flex; align-items: center; gap: 5px;',
      ],
    ];

    // HTML5 color input for display.
    $element['color_wrapper']['color_display'] = [
      '#type' => 'color',
      '#default_value' => $display_value,
      '#attributes' => [
        'id' => $color_input_id,
        'class' => ['vactory-color-picker-input'],
        'data-hidden-input-id' => $hidden_input_id,
        'data-default-color' => self::DEFAULT_DISPLAY_COLOR,
      ],
    ];

    // Clear link (using span element to avoid form submission).
    $element['color_wrapper']['clear'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t('Clear'),
      '#attributes' => [
        'class' => ['vactory-color-picker-clear'],
        'data-color-input-id' => $color_input_id,
        'data-hidden-input-id' => $hidden_input_id,
        'data-default-color' => self::DEFAULT_DISPLAY_COLOR,
        'style' => 'padding: 2px 8px; font-size: 12px; cursor: pointer; border: 1px solid #ccc; border-radius: 3px; background: #f5f5f5; display: inline-block;',
      ],
    ];

    // Attach JavaScript library.
    $element['#attached']['library'][] = 'vactory_color_picker/widget';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      if (isset($value['color'])) {
        $color_value = trim($value['color']);
        // If color value is empty, set it to NULL to delete the field item.
        if (empty($color_value)) {
          $values[$delta]['color'] = NULL;
        }
        else {
          // Validate and normalize the color value.
          $color_service = \Drupal::service('colorapi.service');
          if ($color_service->isValidHexadecimalColorString($color_value)) {
            $values[$delta]['color'] = strtoupper($color_value);
          }
          else {
            // Invalid color, set to NULL.
            $values[$delta]['color'] = NULL;
          }
        }
      }
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

}
