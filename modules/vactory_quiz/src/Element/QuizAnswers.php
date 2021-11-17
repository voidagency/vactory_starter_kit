<?php

namespace Drupal\vactory_quiz\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide Quiz answers form element.
 *
 * @FormElement("quiz_answers")
 */
class QuizAnswers extends FormElement {

  /**
   * Quiz field unlimited cardinality.
   */
  const CARDINALITY_UNLIMITED = -1;

  /**
   * {@inheritDoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [
        [self::class, 'processQuizAnswers'],
      ],
      '#element_validate' => [
        [self::class, 'validateQuizAnswers'],
      ],
      '#theme_wrappers' => ['container'],
      '#cardinality' => self::CARDINALITY_UNLIMITED,
    ];
  }

  /**
   * Quiz form element process callback.
   */
  public static function processQuizAnswers(&$element, FormStateInterface $form_state, &$form) {
    $parents = $element['#parents'];
    $default_value = isset($element['#default_value']) && is_array($element['#default_value']) ? $element['#default_value'] : [];
    // Get element state.
    $element_state = static::getElementState($parents, $form_state);
    if (!isset($element_state['answers_count'])) {
      // Set default answers count to 1.
      $element_state['answers_count'] = !empty($default_value) ? count($default_value) : 1;
      static::setElementState($parents, $form_state, $element_state);
    }
    if (!isset($element_state['removed_answers_indexes'])) {
      // Init removed answers indexes list.
      $element_state['removed_answers_indexes'] = [];
      static::setElementState($parents, $form_state, $element_state);
    }
    $removed_answers_indexes = isset($element_state['removed_answers_indexes']) ? $element_state['removed_answers_indexes'] : [];
    // Get element cardinality, default to unlimited.
    $cardinality = isset($element['#cardinality']) ? $element['#cardinality'] : self::CARDINALITY_UNLIMITED;
    $cardinality = count($removed_answers_indexes) > 0 ? $element['#cardinality'] + count($removed_answers_indexes) : $cardinality;
    $answers_count = isset($element_state['answers_count']) ? $element_state['answers_count'] : 1;

    // Display error messages area.
    $element['status_messages'] = ['#type' => 'status_messages'];

    // Prepare element AJAX wrapper ID.
    $element_id_prefix = implode('-', $parents);
    $element_wrapper_id = str_replace('_', '-', $element_id_prefix . '-add-more-wrapper');
    $element['#prefix'] = '<div id="' . $element_wrapper_id . '">';
    $element['#suffix'] = '</div>';
    $element_name = $element['#name'];
    $element[$element_name] = [
      '#type' => 'vertical_tabs',
      '#tree' => TRUE,
    ];
    $element['answers_tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    // Initialize answer title index.
    $j = 0;
    for ($i = 0; self::cardinalityControl($i, $cardinality, $answers_count); $i++) {
      // We are not interested in removed answers.
      if (!in_array($i, $removed_answers_indexes, TRUE)) {
        $weight = $j;
        $j++;
        $element[$element_name][$i] = [
          '#type' => 'details',
          '#title' => t('Answer @num', ['@num' => $j]),
          '#tree' => TRUE,
          '#group' => $element_name,
          '#open' => $j === ($answers_count - count($removed_answers_indexes)),
        ];
        $element[$element_name][$i]['answer_id'] = [
          '#type' => 'textfield',
          '#title' => t('Answer ID'),
          '#required' => TRUE,
          '#default_value' => isset($default_value[$weight]['answer_id']) ? $default_value[$weight]['answer_id'] : static::getAnswerEnumAlphabet($weight),
          '#attributes' => [
            'disabled' => TRUE,
          ],
        ];
        $element[$element_name][$i]['answer'] = [
          '#type' => 'textfield',
          '#title' => t('Answer'),
          '#default_value' => isset($default_value[$weight]['answer']) ? $default_value[$weight]['answer'] : '',
          '#required' => TRUE,
        ];
        $element[$element_name][$i]['is_correct'] = [
          '#type' => 'radios',
          '#title' => t('Correct'),
          '#options' => [
            0 => t('Wrong answer'),
            1 => t('Correct answer'),
          ],
          '#default_value' => isset($default_value[$weight]['is_correct']) ? $default_value[$weight]['is_correct'] : '',
          '#required' => TRUE,
        ];
        // Render answer remove button only when we have more than one answer.
        if (($answers_count - count($removed_answers_indexes)) > 1) {
          $element[$element_name][$i]['remove_answer'] = [
            '#type' => 'submit',
            '#name' => str_replace('-', '_', $element_id_prefix) . $i . '_remove_answer',
            '#button_type' => 'danger',
            '#value' => t('Remove answer'),
            '#submit' => [[static::class, 'removeAnswer']],
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => [static::class, 'updateAnswerElement'],
              'wrapper' => $element_wrapper_id,
              'effect' => 'fade',
            ],
          ];
        }
      }
    }

    // Render add answer button until we reach element cardinality.
    if ($answers_count < $cardinality || $cardinality < 0) {
      $element['add_answer'] = [
        '#type' => 'submit',
        '#name' => str_replace('-', '_', $element_id_prefix) . '_add_answer',
        '#button_type' => 'primary',
        '#value' => t('Add answer'),
        '#submit' => [[static::class, 'addAnswer']],
        '#ajax' => [
          'callback' => [static::class, 'updateAnswerElement'],
          'wrapper' => $element_wrapper_id,
          'effect' => 'fade',
        ],
        '#weight' => 0,
      ];
    }
    return $element;
  }

  /**
   * Cardinality control function.
   */
  public static function cardinalityControl($index, $cardinality, $answers_count) {
    return $cardinality < 0 || $answers_count <= $cardinality ? $index < $answers_count : $index < $cardinality;
  }

  /**
   * Quiz form element validate callback.
   */
  public static function validateQuizAnswers(&$element, FormStateInterface $form_state, &$form) {
  }

  /**
   * Get element state.
   */
  public static function getElementState(array $parents, FormStateInterface $form_state) {
    return NestedArray::getValue($form_state->getStorage(), $parents);
  }

  /**
   * Set element state.
   */
  public static function setElementState(array $parents, FormStateInterface $form_state, array $field_state) {
    NestedArray::setValue($form_state->getStorage(), $parents, $field_state);
  }

  /**
   * Add more ajax call.
   */
  public static function addAnswer(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
    $parents = $element['#parents'];
    // Increment the items count.
    $element_state = static::getElementState($parents, $form_state);
    $element_state['answers_count']++;
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Delete item ajax.
   */
  public static function removeAnswer(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Go 3 level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -3));
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    // Get removed answer index.
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $index = end($triggering_element_parents);
    // Add removed answer index to removed answers list.
    $element_state['removed_answers_indexes'][] = $index;
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Add more items.
   */
  public static function updateAnswerElement(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
    if (in_array('remove_answer', $triggering_element['#array_parents'], TRUE)) {
      // Go 3 levels up in the form, to the widgets container.
      $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -3));
    }
    return $element;
  }

  /**
   * Get alphabet by given index.
   */
  public static function getAnswerEnumAlphabet($index) {
    $alphabets = range('A', 'Z');
    return $alphabets[$index];
  }

}
