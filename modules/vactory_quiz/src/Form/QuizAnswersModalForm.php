<?php

namespace Drupal\vactory_quiz\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Quiz answers modal form.
 */
class QuizAnswersModalForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'quiz_answers_modal';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::request()->query;
    // Get question answers field name from query.
    if (!empty($query->get('question_answers_field_name'))) {
      $form_state->set('questionAnswersFieldName', $query->get('question_answers_field_name'));
    }
    // Get question answers default values from query.
    if (!empty($query->get('default_answers'))) {
      $form_state->set('defaultAnswers', $query->get('default_answers'));
    }
    // Get answers cardinality from query.
    if (!empty($query->get('answers_cardinality'))) {
      $form_state->set('answersCardinality', $query->get('answers_cardinality'));
    }
    // Get question answers summary wrapper id from query.
    if (!empty($query->get('element_wrapper_id'))) {
      $form_state->set('elementWrapperId', $query->get('element_wrapper_id'));
    }

    $default_answers = !empty($form_state->get('defaultAnswers')) ? $form_state->get('defaultAnswers') : [];
    $cardinality = !empty($form_state->get('answersCardinality')) ? $form_state->get('answersCardinality') : 4;
    $form['question_answers'] = [
      '#type' => 'quiz_answers',
      '#title' => $this->t('Question answers'),
      '#cardinality' => $cardinality,
      '#default_value' => $default_answers,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save answers'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalQuestionAnswers'],
        'event' => 'click',
      ],
    ];
    $form['#prefix'] = '<div id="question-answers-modal-form-wrapper">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * Modal form submit function.
   */
  public function submitModalQuestionAnswers(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Do not close modal form in error case, just render the form.
    if ($form_state->hasAnyErrors()) {
      return $response->addCommand(new ReplaceCommand('#question-answers-modal-form-wrapper', $form));
    }
    $element_wrapper_id = $form_state->get('elementWrapperId');
    $question_answers = $form_state->getValue('question_answers');
    // Normalize question answers list.
    $question_answers = array_filter($question_answers, function ($key) {
      return is_numeric($key);
    }, ARRAY_FILTER_USE_KEY);
    $question_answers = array_map(function ($el) {
      if (isset($el['remove_answer'])) {
        unset($el['remove_answer']);
      }
      return $el;
    }, $question_answers);
    $question_answers = array_values($question_answers);
    $update_widget_button_name = $element_wrapper_id;
    // Encode question answers list to JSON format.
    $question_answers = Json::encode($question_answers);

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('#' . $form_state->get('questionAnswersFieldName'), 'val', ['' . $question_answers]))
      ->addCommand(new CloseDialogCommand('#quiz-answers-dialog-wrapper', FALSE))
      ->addCommand(new InvokeCommand('[name="' . $update_widget_button_name . '"]', 'trigger', ['mousedown']));
    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
