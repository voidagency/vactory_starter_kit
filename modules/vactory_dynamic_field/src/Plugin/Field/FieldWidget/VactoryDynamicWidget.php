<?php

namespace Drupal\vactory_dynamic_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\vactory_dynamic_field\WidgetsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'field_wysiwyg_dynamic_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_wysiwyg_dynamic_widget",
 *   module = "vactory_dynamic_field",
 *   label = @Translation("Dynamic Field Widget"),
 *   description = @Translation("Allows you to select widgets from the
 *   components library."), field_types = {
 *     "field_wysiwyg_dynamic"
 *   },
 *   multiple_values = FALSE,
 * )
 */
class VactoryDynamicWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $widgetsManager;

  /**
   * {@inheritdoc}
   *
   * Create a new instance.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('vactory_dynamic_field.vactory_provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, WidgetsManagerInterface $platformProvider) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->widgetsManager = $platformProvider;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    $elements['add_more']['#attributes']['class'][] = 'js-hide';
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    // Load the items for form rebuilds from the field state.
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality == 1) {
      $field_state = static::getWidgetState($form['#parents'], $this->fieldDefinition->getName(), $form_state);
      if (isset($field_state['widget_id']) && isset($field_state['widget_data'])) {
        $items->setValue([
          'widget_id' => $field_state['widget_id'],
          'widget_data' => $field_state['widget_data'],
        ]);
      }
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];
    // The $field_name $field_bundle and $entity_type_id used to get the field
    // Definitions and settings.
    // @see vactory_dynamic_field\Form\ModalForm::buildWidgetSelectorForm().
    $field_name = $this->fieldDefinition->getName();
    $field_bundle = $this->fieldDefinition->getTargetBundle();
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();
    $parents = $form['#parents'];
    $id_suffix = '-' . implode('-', $parents) . '-' . $delta;
    $wrapper_id = $field_name . '-dynamic-library-wrapper' . $id_suffix;
    $limit_validation_errors = [array_merge($parents, [$field_name])];
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $add_more_button = implode('_', array_slice($element['#field_parents'], 0)) . '_' . $field_name . '_add_more';

    // Data.
    $widget_id = isset($item->widget_id) ? $item->widget_id : NULL;
    $widget_data = isset($item->widget_data) ? $item->widget_data : [];
    $element += [
      '#type' => 'fieldset',
      '#attributes' => [
        'id' => $wrapper_id,
        'class' => [
          'dynamic-library-widget',
          (!$widget_id && $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) ? 'multiple-choose-template' : '',
        ],
        'add-more-button' => $add_more_button,
      ],
      '#attached' => [
        'library' => ['core/drupal.ajax', 'core/drupal.dialog.ajax'],
      ],
    ];

    // Comment this to show the field title.
    // $element['#title_display'] = 'invisible';.
    $settings = ($widget_id) ? $this->widgetsManager->loadSettings($widget_id) : [];
    if ($widget_id) {
      $element['current_selection'] = [
        '#theme' => 'vactory_dynamic_current_widget',
        '#widget' => $settings,
      ];
    }

    // Params to pass to modal.
    $query = [
      'field_name' => $field_name,
      'field_bundle' => $field_bundle,
      'entity_type_id' => $entity_type_id,
      'field_id' => $field_name . $id_suffix,
      'widget_id' => $widget_id,
     // 'widget_data' => $widget_data,
      'wrapper_id' => $wrapper_id,
      'cardinality' => $cardinality,
    ];

    // Modal link opener.
    $element['open_modal'] = [
      '#type' => 'link',
      '#title' => (!$widget_id) ? $this->t('Choose template') : $this->t('Edit template'),
      '#url' => Url::fromRoute('vactory_dynamic_field.open_modal_form', [], [
        'query' => $query,
      ]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
        ],
        'data-dialog-options' => json_encode([
          'data' => $widget_data,
        ]),
      ],
    ];

    // Clear widget.
    if ($widget_id && $cardinality !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $element['clear_widget'] = [
        '#type' => 'submit',
        '#name' => $field_name . '-dynamic-library-remove' . $id_suffix,
        '#value' => $this->t('Remove'),
        '#button_type' => 'danger',
        '#attributes' => [
          'data-dynamic-widget-remove' => $field_name . $id_suffix,
          'class' => ['widget-library-item__remove'],
          'aria-label' => $this->t('Remove @label', ['@label' => $settings['name']]),
        ],
        '#ajax' => [
          'callback' => [static::class, 'removeWidget'],
          'wrapper'  => $wrapper_id,
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Removing @label.', ['@label' => $settings['name']]),
          ],
        ],
        '#submit' => [[static::class, 'removeItem']],
        // Prevent errors in other widgets from preventing removal.
        '#limit_validation_errors' => $limit_validation_errors,
      ];
    }

    // Widget ID.
    $element['widget_id'] = [
      '#type'          => 'hidden',
      '#default_value' => $widget_id,
      '#attributes'    => [
        // This is used to pass the selection from the modal to the widget.
        'data-dynamic-widget-id' => $field_name . $id_suffix,
      ],
    ];

    // Widget Data.
    $element['widget_data'] = [
      '#type'          => 'hidden',
      '#default_value' => $widget_data,
      '#attributes'    => [
        // This is used to pass the selection from the modal to the widget.
        'data-dynamic-widget-value' => $field_name . $id_suffix,
      ],
    ];

    // When a selection is made this hidden button is pressed to update widget
    // data based on the "widget_id" value.
    $element['widget_update_form'] = [
      '#type'                    => 'submit',
      '#value'                   => $this->t('Update widget'),
      '#name'                    => $field_name . '-dynamic-library-update' . $id_suffix,
      '#ajax'                    => [
        'callback' => [static::class, 'updateWidget'],
        'wrapper'  => $wrapper_id,
      ],
      '#attributes'              => [
        'data-dynamic-widget-update' => $field_name . $id_suffix,
        'class'                      => ['js-hide'],
      ],
      '#submit'                  => [[static::class, 'updateItems']],

      // Prevent errors in other widgets from preventing updates.
      '#limit_validation_errors' => $limit_validation_errors,
    ];

    return $element;
  }

  /**
   * AJAX callback to update the widget when the selection changes.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array representing the updated widget.
   */
  public static function updateWidget(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    return $element;
  }

  /**
   * AJAX callback to remove the widget when the selection changes.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array representing the updated widget.
   */
  public static function removeWidget(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    $element['widget_id']['#value'] = '';
    $element['widget_data']['#value'] = '';
    return $element;
  }

  /**
   * Updates the field state and flags the form for rebuild.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function updateItems(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $state_element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -2));
    $element = NestedArray::getValue($form, $parents);
    $field_state = static::getFieldState($state_element, $form_state);
    $widget = static::getNewWidget($element, $form_state);

    if (!empty($widget)) {
      $field_state['widget_id'] = $widget['widget_id'];
      $field_state['widget_data'] = $widget['widget_data'];
      static::setFieldState($state_element, $form_state, $field_state);
    }

    $form_state->setRebuild();
  }

  /**
   * Remove the field state and flags the form for rebuild.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function removeItem(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $state_element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -2));
    $field_state = static::getFieldState($state_element, $form_state);
    $field_state['widget_id'] = '';
    $field_state['widget_data'] = '';
    static::setFieldState($state_element, $form_state, $field_state);
    $form_state->setRebuild();
  }

  /**
   * Gets newly selected media items.
   *
   * @param array $element
   *   The wrapping element for this widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array of selected widget items.
   */
  protected static function getNewWidget(array $element, FormStateInterface $form_state) {
    // Get the new widget ID passed to our hidden button.
    $values = $form_state->getValues();
    $path = $element['#parents'];
    $value = NestedArray::getValue($values, $path);

    if (!empty($value['widget_id']) && !empty($value['widget_data'])) {
      return [
        'widget_id'   => $value['widget_id'],
        'widget_data' => $value['widget_data'],
      ];
    }

    return [];
  }

  /**
   * Gets the field state for the widget.
   *
   * @param array $element
   *   The wrapping element for this widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array[]
   *   An array with the following key/value pairs:
   *   - widget_id: (int) The widget ID.
   *   - widget_data: (string) The widget data.
   */
  protected static function getFieldState(array $element, FormStateInterface $form_state) {
    // Default to using the current selection if the form is new.
    $path = $element['#parents'];
    $values = NestedArray::getValue($form_state->getValues(), $path);
    $widget_id = isset($values['widget_id']) ? $values['widget_id'] : NULL;
    $widget_data = isset($values['widget_data']) ? $values['widget_data'] : [];

    $widget_state = static::getWidgetState($element['#field_parents'], $element['#field_name'], $form_state);
    $widget_state['widget_id'] = isset($widget_state['widget_id']) ? $widget_state['widget_id'] : $widget_id;
    $widget_state['widget_data'] = isset($widget_state['widget_data']) ? $widget_state['widget_data'] : $widget_data;
    return $widget_state;
  }

  /**
   * Sets the field state for the widget.
   *
   * @param array $element
   *   The wrapping element for this widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array[] $field_state
   *   An array with the following key/value pairs:
   *   - widget_id: (int) The widget ID.
   *   - widget_data: (string) The widget data.
   */
  protected static function setFieldState(array $element, FormStateInterface $form_state, array $field_state) {
    static::setWidgetState($element['#field_parents'], $element['#field_name'], $form_state, $field_state);
  }

}
