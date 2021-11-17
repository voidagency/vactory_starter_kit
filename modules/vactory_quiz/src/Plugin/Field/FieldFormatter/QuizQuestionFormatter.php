<?php

namespace Drupal\vactory_quiz\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\vactory_quiz\Services\VactoryQuizManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Quiz Question field formatter.
 *
 * @FieldFormatter(
 *   id = "vactory_quiz_question_formatter",
 *   label = @Translation("Quiz Question Default"),
 *   field_types = {
 *     "vactory_quiz_question"
 *   }
 * )
 */
class QuizQuestionFormatter extends FormatterBase {

  /**
   * Quiz manager service.
   *
   * @var \Drupal\vactory_quiz\Services\VactoryQuizManager
   */
  protected $quizManager;

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    VactoryQuizManager $quizManager,
    FormBuilderInterface $formBuilder,
    ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );
    $this->quizManager = $quizManager;
    $this->formBuilder = $formBuilder;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('vactory_quiz.manager'),
      $container->get('form_builder'),
      $container->get('module_handler')
    );
  }

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $current_user = \Drupal::currentUser();
    $quiz = $items->getEntity();
    $results = [];
    $questions = $items->getValue();
    $questions = array_map(function ($question) {
      $question['question_answers'] = Json::decode($question['question_answers']);
      return $question;
    }, $questions);
    $perfect_mark = $this->quizManager->getPerfectMark($quiz->id());
    $attempt_history_exist = FALSE;
    if ($this->moduleHandler->moduleExists('vactory_quiz_history')) {
      $results = $this->quizManager->getQuizUserAttemptHistory($quiz->id(), $current_user->id());
      $user_attempt_history = $this->quizManager->getQuizUserAttemptHistory($quiz->id(), $current_user->id());
      if (!empty($user_attempt_history)) {
        $attempt_history_exist = TRUE;
      }
    }
    $quiz = [
      'questions' => $questions,
      'entity_id' => $items->getEntity()->id(),
      'entity_type' => $items->getEntity()->getEntityTypeId(),
      'results' => $results,
      'attempt_history_exist' => $attempt_history_exist,
      'perfect_mark' => $perfect_mark,
    ];

    return $this->formBuilder->getForm('Drupal\vactory_quiz\Form\QuizForm', $quiz);
  }

}
