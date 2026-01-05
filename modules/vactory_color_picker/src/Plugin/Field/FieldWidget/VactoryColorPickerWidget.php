<?php

namespace Drupal\vactory_color_picker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
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
   *
   * This is the color shown in the color picker when no value is set.
   * Changing this constant will update the default color everywhere.
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

    // Get the actual value from the field item, or empty if not set.
    $actual_value = isset($items[$delta]) && $items[$delta]->getHexadecimal()
      ? $items[$delta]->getHexadecimal()
      : '';

    // Ensure the value is a valid hex color if it exists.
    if (!empty($actual_value) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $actual_value)) {
      $actual_value = '';
    }

    // Display value for color input (use default color when no value is set).
    $display_value = !empty($actual_value) ? $actual_value : self::DEFAULT_DISPLAY_COLOR;

    // Container for color input and clear button.
    $wrapper_id = 'color-picker-wrapper-' . $delta . '-' . md5(serialize($element['#field_parents'] ?? []));
    $element['color_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; align-items: center; gap: 5px;',
        'id' => $wrapper_id,
      ],
    ];

    // Hidden input to store the actual value.
    $hidden_input_id = 'color-picker-hidden-' . $delta . '-' . md5(serialize($element['#field_parents'] ?? []));
    $element['color_wrapper']['color'] = [
      '#type' => 'hidden',
      '#default_value' => $actual_value,
      '#attributes' => [
        'id' => $hidden_input_id,
      ],
    ];

    // HTML5 color input for display only.
    $color_input_id = 'color-picker-input-' . $delta . '-' . md5(serialize($element['#field_parents'] ?? []));
    $element['color_wrapper']['color_display'] = [
      '#type' => 'color',
      '#default_value' => $display_value,
      '#attributes' => [
        'id' => $color_input_id,
        'data-hidden-input-id' => $hidden_input_id,
      ],
    ];

    // Clear button.
    $element['color_wrapper']['clear'] = [
      '#type' => 'button',
      '#value' => $this->t('Clear'),
      '#ajax' => [
        'callback' => [static::class, 'clearColorAjax'],
        'event' => 'click',
      ],
      '#attributes' => [
        'style' => 'padding: 2px 8px; font-size: 12px;',
        'data-color-input-id' => $color_input_id,
        'data-hidden-input-id' => $hidden_input_id,
      ],
    ];

    // Keep color at root level for parent class compatibility.
    $element['color'] = &$element['color_wrapper']['color'];

    // Attach JavaScript library to sync color_display with hidden color input.
    $element['#attached']['library'][] = 'vactory_color_picker/widget';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      // If color value is empty, set it to NULL to delete the field item.
      if (isset($value['color']) && empty(trim($value['color']))) {
        $values[$delta]['color'] = NULL;
      }
      // Validate and normalize the color value if it exists.
      elseif (isset($value['color']) && !empty(trim($value['color']))) {
        $color_service = \Drupal::service('colorapi.service');
        if ($color_service->isValidHexadecimalColorString($value['color'])) {
          $values[$delta]['color'] = strtoupper($value['color']);
        }
        else {
          // Invalid color, set to NULL to delete.
          $values[$delta]['color'] = NULL;
        }
      }
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * AJAX callback to clear the color value.
   */
  public static function clearColorAjax(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $color_input_id = $triggering_element['#attributes']['data-color-input-id'] ?? '';
    $hidden_input_id = $triggering_element['#attributes']['data-hidden-input-id'] ?? '';

    $response = new AjaxResponse();

    if ($color_input_id && $hidden_input_id) {
      // Clear the hidden input (empty value = NULL = delete from database).
      $response->addCommand(new InvokeCommand('#' . $hidden_input_id, 'val', ['']));
      // Reset the display color input to default color for visual feedback.
      $response->addCommand(new InvokeCommand('#' . $color_input_id, 'val', [self::DEFAULT_DISPLAY_COLOR]));
      // Trigger change event to ensure form state is updated.
      $response->addCommand(new InvokeCommand('#' . $hidden_input_id, 'trigger', ['change']));
    }

    return $response;
  }

}
