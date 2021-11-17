<?php

namespace Drupal\vactory_sondage\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Widget for Sondage Option field.
 *
 * @FieldWidget(
 *   id = "vactory_sondage_option_widget",
 *   label = @Translation("Sondage option Widget"),
 *   field_types = {
 *     "vactory_sondage_option"
 *   }
 * )
 */
class SondageOptionWidget extends WidgetBase {

  /**
   * Returns the form for a single field widget.
   *
   * Field widget form elements should be based on the passed-in $element, which
   * contains the base form element properties derived from the field
   * configuration.
   *
   * The BaseWidget methods will set the weight, field name and delta values for
   * each form element. If there are multiple values for this field, the
   * formElement() method will be called as many times as needed.
   *
   * Other modules may alter the form element provided by this function using
   * hook_field_widget_form_alter() or
   * hook_field_widget_WIDGET_TYPE_form_alter().
   *
   * The FAPI element callbacks (such as #process, #element_validate,
   * #value_callback, etc.) used by the widget do not have access to the
   * original $field_definition passed to the widget's constructor. Therefore,
   * if any information is needed from that definition by those callbacks, the
   * widget implementing this method, or a
   * hook_field_widget[_WIDGET_TYPE]_form_alter() implementation, must extract
   * the needed properties from the field definition and set them as ad-hoc
   * $element['#custom'] properties, for later use by its element callbacks.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   * @param array $element
   *   A form element array containing basic properties for the widget:
   *   - #field_parents: The 'parents' space for the field in the form. Most
   *       widgets can simply overlook this property. This identifies the
   *       location where the field values are placed within
   *       $form_state->getValues(), and is used to access processing
   *       information for the field through the getWidgetState() and
   *       setWidgetState() methods.
   *   - #title: The sanitized element label for the field, ready for output.
   *   - #description: The sanitized element description for the field, ready
   *     for output.
   *   - #required: A Boolean indicating whether the element value is required;
   *     for required multiple value fields, only the first widget's values are
   *     required.
   *   - #delta: The order of this item in the array of sub-elements; see $delta
   *     above.
   * @param array $form
   *   The form structure where widgets are being attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form elements for a single widget for this field.
   *
   * @see hook_field_widget_form_alter()
   * @see hook_field_widget_WIDGET_TYPE_form_alter()
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $route_name = \Drupal::routeMatch()->getRouteName();
    // Element fields are not required on field config page.
    $required = strpos($route_name, 'entity.field_config') !== 0 && $delta < 2;
    $default_text = isset($items[$delta]->option_text) ? $items[$delta]->option_text : '';
    $default_image = isset($items[$delta]->option_image) ? $items[$delta]->option_image : '';
    $default_type = !empty($default_text) ? 'text' : '';
    $default_type = empty($default_type) && !empty($default_image) ? 'image' : $default_type;

    $element += [
      '#type' => 'fieldset',
    ];
    // Sondage option value input.
    $element['option_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option ID'),
      '#attributes' => [
        'disabled' => TRUE,
      ],
      '#default_value' => 'option_' . ($delta + 1),
      '#required' => $required,
    ];
    // Sondage option label input.
    $element['option_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Option type'),
      '#options' => [
        'text' => $this->t('Text'),
        'image' => $this->t('image'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#required' => $required,
      '#default_value' => $default_type,
    ];
    // Sondage option label input.
    $element['option_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Option text'),
      '#default_value' => $default_text,
      '#description' => $this->t('Enter sondage option text'),
      '#states' => [
        'visible' => [
          "[name=\"$field_name\\[$delta\\][option_type]\"]" => ['value' => 'text'],
        ],
      ],
    ];
    // Sondage option image.
    $element['image_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          "[name=\"$field_name\\[$delta\\][option_type]\"]" => ['value' => 'image'],
        ],
      ],
    ];
    $element['image_container']['option_image'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Option image'),
      '#default_value' => $default_image,
      '#allowed_bundles' => ['image'],
      '#description' => $this->t('Upload or select option image.'),
    ];

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (isset($value['image_container'])) {
        $value['option_image'] = $value['image_container']['option_image'];
      }
      unset($value['image_container']);
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
