<?php

namespace Drupal\vactory_quiz\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Widget for Quiz Question field.
 *
 * @FieldWidget(
 *   id = "vactory_quiz_question_widget",
 *   label = @Translation("Quiz Question Widget"),
 *   field_types = {
 *     "vactory_quiz_question"
 *   }
 * )
 */
class QuizQuestionWidget extends WidgetBase {

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings() {
    return [
      // Set default cardinality to 4.
      'answers_cardinality' => 4,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['answers_cardinality'] = [
      '#type' => 'number',
      '#title' => $this->t('Answers cardinality'),
      '#min' => 0,
      '#description' => $this->t('Enter question answers cardinality. Enter 0 for an unlimitted cardinality'),
      '#default_value' => $this->getSetting('answers_cardinality'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Answers cardinality: @cardinality', ['@cardinality' => $this->getSetting('answers_cardinality')]);
    return $summary;
  }

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
   * hook_field_widget_single_element_form_alter() or
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
   * @see hook_field_widget_single_element_form_alter()
   * @see hook_field_widget_WIDGET_TYPE_form_alter()
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $route_name = \Drupal::routeMatch()->getRouteName();
    // Element fields are not required on field config page.
    $required = $route_name !== 'entity.field_config.node_field_edit_form' && $delta < 1;
    $form_build_info = $form_state->getBuildInfo();
    $form_id = $form_build_info['form_id'];
    $node = \Drupal::routeMatch()->getParameter('node');
    $suffix = $node instanceof NodeInterface ? '_node_' . $node->id() : '_node_add';
    $tempstore = \Drupal::service('tempstore.private');
    $store = $tempstore->get('vactory_quiz_' . $form_id . $suffix);
    // Clean widget when necessary.
    $this->cleanWidgetValues($store, $element, $form_state, $delta);
    // Set question number to element delta value + 1.
    $question_number = $delta + 1;
    $widget_state = $this->getWidgetStore($store, $items, $delta);
    $question_type = $widget_state['question_type'];
    $question_reward = $widget_state['question_reward'];
    $question_penalty = $widget_state['question_penalty'];
    $question_text_value = $widget_state['question_text_value'];
    $question_text_format = $widget_state['question_text_format'];
    $question_answers = $widget_state['question_answers'];
    $field_name = $this->fieldDefinition->getName();
    $open = $widget_state['open'];

    // Element ajax wrapper ID.
    $wrapper_id = $field_name . '-element-ajax-wrapper-' . $delta;
    // Question answer field name.
    $question_answers_field_name = 'quiz_question_answers_' . $delta;
    // Get question answers from json.
    $answers = Json::decode($question_answers);
    // Prepare query params form modal form.
    $query = [
      'question_answers_field_name' => $question_answers_field_name,
      'question_number' => $delta + 1,
      'default_answers' => $answers,
      'answers_cardinality' => $this->getSetting('answers_cardinality'),
      'element_wrapper_id' => $wrapper_id,
    ];

    $element += [
      '#type' => 'details',
      '#title' => 'Question @num',
      '#open' => $open,
      '#element_validate' => [
        [$this, 'validate'],
      ],
      '#attributes' => [
        'id' => $wrapper_id,
      ],
    ];
    // Question number input.
    $element['question_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Question number'),
      '#value' => $question_number,
      '#attributes' => [
        'disabled' => TRUE,
      ],
      '#required' => $required,
    ];
    // Question type input.
    $element['question_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Question type'),
      '#options' => [
        'multiple' => $this->t('Question à choix multiple'),
        'unique' => $this->t('Question à choix unique'),
      ],
      '#default_value' => $question_type,
      '#value' => $question_type,
      '#required' => $required,
    ];
    // Question reward input.
    $element['question_reward'] = [
      '#type' => 'number',
      '#title' => $this->t('Question reward'),
      '#default_value' => $question_reward,
      '#value' => $question_reward,
      '#min' => 1,
      '#required' => $required,
    ];
    // Question penalty input.
    $element['question_penalty'] = [
      '#type' => 'number',
      '#title' => $this->t('Question penalty'),
      '#default_value' => $question_penalty,
      '#value' => $question_penalty,
      '#max' => 0,
      '#required' => $required,
    ];
    // Question value wysiwyg field.
    $element['question'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Question'),
      '#default_value' => $question_text_value,
      '#value' => $question_text_value,
      '#format' => $question_text_format,
      '#required' => $required,
    ];
    // Question value value.
    $element['question_text_value'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Question'),
      '#default_value' => $question_text_value,
      '#value' => $question_text_value,
    ];
    // Question value format.
    $element['question_text_format'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Question text format'),
      '#default_value' => $question_text_format,
      '#value' => $question_text_format,
    ];
    // Question answers.
    $element['question_answers'] = [
      '#type' => 'hidden',
      '#default_value' => $question_answers,
      '#value' => $question_answers,
      '#attributes' => [
        'id' => $question_answers_field_name,
      ],
    ];
    // Question answers summary wrapper.
    $element['question_answers_summary_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Answers summary'),
      '#attributes' => [
        'style' => 'background-color: aliceblue',
      ],
      '#required' => $required,
    ];
    // Question summary template.
    $answer_summary = [
      '#theme' => 'vactory_quiz_answer_summary',
      '#content' => [
        'answers' => $answers,
        'wrapper_id' => $wrapper_id,
      ],
      '#cache' => ['max-age' => 0],
    ];
    $element['question_answers_summary_wrapper']['answer_summary'] = $answer_summary;
    // Add or edit answer button.
    $element['question_answers_element'] = [
      '#type' => 'link',
      '#button_type' => 'primary',
      '#id' => $wrapper_id . '-edit-answers',
      '#title' => !empty($answers) ? $this->t('Edit answers') : $this->t('Add answers'),
      '#url' => Url::fromRoute('vactory_quiz.answers_modal_form', [], [
        'query' => $query,
      ]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'button--primary',
        ],
      ],
    ];
    // Update question widget after closing modal form.
    $element['update_widget'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update widget'),
      '#name' => $wrapper_id,
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'updateWidgetForm'],
        'wrapper' => $wrapper_id,
      ],
      '#submit' => [[$this, 'updateWidgetState']],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
    ];

    // Attach drupal dialog ajax library form modal form.
    $element['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $element;
  }

  /**
   * Update widget state callback.
   */
  public function updateWidgetState(array $form, FormStateInterface $form_state) {
    // Get triggering element.
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $index = end($parents);
    $element = NestedArray::getValue($form, $parents);
    $widget = static::getNewWidget($element, $form_state);
    if (!empty($widget)) {
      // Update element state values.
      $form_build_info = $form_state->getBuildInfo();
      $form_id = $form_build_info['form_id'];
      $node = \Drupal::routeMatch()->getParameter('node');
      $suffix = $node instanceof NodeInterface ? '_node_' . $node->id() : '_node_add';
      $tempstore = \Drupal::service('tempstore.private');
      $store = $tempstore->get('vactory_quiz_' . $form_id . $suffix);
      $widget_state = $store->get('widget_state');
      $widget_state[$index]['question_type'] = $widget['question_type'];
      $widget_state[$index]['question_reward'] = $widget['question_reward'];
      $widget_state[$index]['question_penalty'] = $widget['question_penalty'];
      $widget_state[$index]['question_text_value'] = $widget['question_text_value'];
      $widget_state[$index]['question_text_format'] = $widget['question_text_format'];
      $widget_state[$index]['question_answers'] = $widget['question_answers'];
      $widget_state[$index]['open'] = TRUE;
      $store->set('widget_state', $widget_state);
    }

    $form_state->setRebuild();

  }

  /**
   * Returns element submitted values.
   */
  protected static function getNewWidget(array $element, FormStateInterface $form_state) {
    // Get submitted data from form state user input.
    $values = $form_state->getUserInput();
    $path = $element['#parents'];
    $values = NestedArray::getValue($values, $path);
    if (!empty($values)) {
      return [
        'question_type' => $values['question_type'],
        'question_reward' => $values['question_reward'],
        'question_penalty' => $values['question_penalty'],
        'question_text_value' => $values['question']['value'],
        'question_text_format' => $values['question']['format'],
        'question_answers' => $values['question_answers'],
      ];
    }

    return [];
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
    $route_name = \Drupal::routeMatch()->getRouteName();
    $form_build_info = $form_state->getBuildInfo();
    $form_id = $form_build_info['form_id'];
    $tempstore = \Drupal::service('tempstore.private');
    $node = \Drupal::routeMatch()->getParameter('node');
    $suffix = $node instanceof NodeInterface ? '_node_' . $node->id() : '_node_add';
    $store = $tempstore->get('vactory_quiz_' . $form_id . $suffix);
    $widget_state = $store->get('widget_state');
    // Disable validation on field settings page.
    if ($route_name !== 'entity.field_config.node_field_edit_form') {
      $widget = static::getNewWidget($element, $form_state);
      $question_answers = json_decode($widget['question_answers'], TRUE);
      if (empty($widget['question_text_value']) || empty($widget['question_text_format'])) {
        $form_state->setError($element['question'], $this->t('The question value field is required'));
        $element['#attributes']['has_error'] = TRUE;
      }
      if (empty($question_answers)) {
        $form_state->setError($element['question_answers_summary_wrapper']['answer_summary'], $this->t('The question answers field is required'));
        $element['#attributes']['has_error'] = TRUE;
      }
      if (empty($widget['question_reward']) || $widget['question_reward'] < 1) {
        $form_state->setError($element['question_reward'], $this->t('The question reward field must be greater than or equal 1'));
        $element['#attributes']['has_error'] = TRUE;
      }

      // Update widget state.
      $widget_state[$element['#delta']] = $widget;
      $widget_state[$element['#delta']]['open'] = FALSE;
      $store->set('widget_state', $widget_state);
      $submitted_values = $form_state->getValues();
      $input_values = $form_state->getUserInput();

      // Update question value and format fields values.
      $path = $element['#parents'];
      $input_values = NestedArray::getValue($input_values, $path);
      $submitted_value = NestedArray::getValue($submitted_values, $path);
      $submitted_value['question_text_value'] = $input_values['question']['value'];
      $submitted_value['question_text_format'] = $input_values['question']['format'];
      NestedArray::setValue($submitted_values, $path, $submitted_value);
      $form_state->setValues($submitted_values);
    }
  }

  /**
   * Clean widget values when necessary.
   */
  public function cleanWidgetValues($store, &$element, FormStateInterface $form_state, $delta) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element)) {
      $parents = $triggering_element['#array_parents'];
      $is_remove = isset($triggering_element) ? in_array('remove_button', $parents, TRUE) : FALSE;
      if (!\Drupal::request()->isXmlHttpRequest()) {
        $store->delete('widget_state');
        $store->delete('is_removed');
      }
      if ($is_remove && !isset($triggering_element['processed'])) {
        $widget_state = $store->get('widget_state');
        $parents = array_slice($parents, 0, -1);
        $index = end($parents);
        unset($widget_state[$index]);
        ksort($widget_state, SORT_NUMERIC);
        $store->set('is_removed', TRUE);
        $store->set('widget_state', array_values($widget_state));
        $triggering_element['processed'] = TRUE;
        $form_state->setTriggeringElement($triggering_element);
      }
    }
  }

  /**
   * Get widget state.
   */
  public function getWidgetStore($store, $items, $delta) {
    $widget_state = $store->get('widget_state');
    if (!isset($widget_state[$delta]) && !$store->get('is_removed')) {
      $widget_state[$delta] = [
        'question_type' => isset($items[$delta]->question_type) ? $items[$delta]->question_type : 'multiple',
        'question_reward' => isset($items[$delta]->question_reward) ? $items[$delta]->question_reward : 1,
        'question_penalty' => isset($items[$delta]->question_penalty) ? $items[$delta]->question_penalty : 0,
        'question_text_value' => isset($items[$delta]->question_text_value) ? $items[$delta]->question_text_value : '',
        'question_text_format' => isset($items[$delta]->question_text_format) ? $items[$delta]->question_text_format : 'basic_html',
        'question_answers' => isset($items[$delta]->question_answers) ? $items[$delta]->question_answers : '[]',
        'open' => isset($items[$delta]->question_type) ? FALSE : TRUE,
      ];
    }
    if (isset($widget_state) && (empty($widget_state) || !isset($widget_state[$delta]))) {
      $widget_state[$delta] = [
        'question_type' => 'multiple',
        'question_reward' => 1,
        'question_penalty' => 0,
        'question_text_value' => '',
        'question_text_format' => 'basic_html',
        'question_answers' => '[]',
        'open' => TRUE,
      ];
    }
    $store->set('widget_state', $widget_state);
    return $widget_state[$delta];
  }

}
