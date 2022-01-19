<?php

namespace Drupal\vactory_cross_content\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Plugin implementation of the 'field_cross_content_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_cross_content_widget_autocomplete",
 *   module = "vactory_cross_content",
 *   label = @Translation("Cross Content Autocomplete"),
 *   field_types = {
 *     "field_cross_content"
 *   },
 *   multiple_values = TRUE
 * )
 */
class CrossContentAutocomplete extends WidgetBase {

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
    $type = $form_state->getFormObject()->getEntity()->bundle();
    $node_type = NodeType::load($type);
    if (!isset($node_type)) {
      $type = $form_state->getFormObject()->getEntity()->get('bundle');
      $node_type = NodeType::load($type);
    }
    $bundles = [$type];
    if ($node_type->getThirdPartySetting('vactory_cross_content', 'enabling', '') == 1) {
      $content_type_selected = $node_type->getThirdPartySetting('vactory_cross_content', 'content_type', []);
      $bundles = is_array($content_type_selected) ? array_merge($bundles, $content_type_selected) : $bundles;
    }
    $bundles = array_values($bundles);
    $node = \Drupal::routeMatch()->getParameter('node');
    $suffix = $node ? $node->id() : $node_type->id();
    $tempstore = \Drupal::service('tempstore.private');
    $store = $tempstore->get('vactpry_cross_content_' . $suffix);
    if (!\Drupal::request()->isXmlHttpRequest()) {
      $store->delete('storedValues');
    }
    if (empty($store->get('storedValues'))) {
      $store->set('storedValues', []);
      $store->set('storedAurocompleteState', []);
    }
    $stored_values = $store->get('storedValues');
    $default_options = !empty($items->getValue()[0]) ? explode(' ', trim($items->getValue()[0]['value'])) : [];
    $default_options = !empty($stored_values) ? $stored_values : $default_options;
    $element += [
      '#type' => 'container',
      '#attributes' => ['id' => 'cross-content-field-container'],
    ];
    $element['autocomplete_widgets'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Contenu lié'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No content'),
      '#tableselect' => FALSE,
      '#element_validate' => [
        [$this, 'validate'],
      ],
      '#attributes' => [
        'class' => ['cross-content-sortable-table'],
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'order-weight-element',
        ],
      ],
    ];
    $cardinality = count($default_options) > 0 ? count($default_options) : 1;
    for ($i = 0; $i < $cardinality; $i++) {
      $nid = isset($default_options[$i]) ? $default_options[$i] : '';
      $default_entity = is_numeric($nid) ? Node::load($nid) : NULL;
      $element['autocomplete_widgets'][$i]['#attributes']['class'][] = 'draggable';
      $element['autocomplete_widgets'][$i]['#weight'] = $i;
      $element['autocomplete_widgets'][$i]['node'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#default_value' => $default_entity,
        '#selection_handler' => 'default',
        '#selection_settings' => [
          'target_bundles' => $bundles,
        ],
        '#multiple' => FALSE,
        '#tag' => FALSE,
      ];
      $element['autocomplete_widgets'][$i]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title'),
        '#title_display' => 'invisible',
        '#default_value' => $i,
        '#value' => $i,
        '#attributes' => [
          'class' => ['order-weight-element'],
        ],
      ];
      unset($default_entity);
    }
    $element['add_one'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more cross content'),
      '#submit' => [[$this, 'updateWidgetState']],
      '#ajax' => [
        'callback' => [$this, 'updateWidgetForm'],
        'wrapper' => 'cross-content-field-container',
      ],
    ];
    $element['value'] = [
      '#type' => 'hidden',
      '#default_value' => implode(' ', $default_options),
    ];
    return $element;
  }

  /**
   * Update widget state callback.
   */
  public function updateWidgetState(array $form, FormStateInterface $form_state) {
    // Get triggering element.
    $user_input = $form_state->getUserInput();
    $node = \Drupal::routeMatch()->getParameter('node');
    $node_type = \Drupal::routeMatch()->getParameter('node_type');
    $suffix = $node ? $node->id() : $node_type->id();
    $tempstore = \Drupal::service('tempstore.private');
    $store = $tempstore->get('vactpry_cross_content_' . $suffix);
    $values = $form_state->getValues();
    $triggering_element = $form_state->getTriggeringElement();
    $field_name = reset($triggering_element['#array_parents']);
    $widget_values = array_map(function ($el) {
      return $el['node'];
    }, $values[$field_name]['autocomplete_widgets']);
    $widget_values = array_values($widget_values);
    // Add new empty autocomplete input.
    $widget_values[] = '';
    $store->set('storedValues', $widget_values);
    $user_input[$field_name]['value'] = implode(' ', $widget_values);
    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
  }

  /**
   * Update widget form callback.
   */
  public function updateWidgetForm(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    return $element;
  }

  /**
   * Element validate Callback.
   */
  public function validate(&$element, FormStateInterface $form_state, &$complete_form) {
    $triggering_element = $form_state->getTriggeringElement();
    $field_name = reset($triggering_element['#array_parents']);
    $values = $form_state->getValues();
    if ($field_name !== 'actions') {
      $widget_values = $values[$field_name]['autocomplete_widgets'];
      foreach ($widget_values as $key => $value) {
        if (empty($value['node'])) {
          $form_state->setError($element['autocomplete_widgets'][$key]['node'], $this->t('Ce champ est requis'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $field_values = [];
    if (isset($values['autocomplete_widgets']) && is_array($values['autocomplete_widgets'])) {
      $field_values = array_map(function ($el) {
        return $el['node'];
      }, $values['autocomplete_widgets']);
    }
    $values['value'] = implode(' ', $field_values);
    return parent::massageFormValues($values, $form, $form_state);
  }

}
