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

    $element['color'] = [
      '#type' => 'vactory_color_picker',
      '#title' => $this->t('Test Color Picker'),
      '#description' => $this->t('Select a color using the HTML5 color picker.'),
      '#default_value' => $actual_value,
      '#clear_button' => TRUE,
      '#clear_button_text' => 'Clear',
    ];

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
