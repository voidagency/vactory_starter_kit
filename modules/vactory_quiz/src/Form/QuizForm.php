<?php

namespace Drupal\vactory_quiz\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\vactory_core\Services\VactoryDevTools;
use Drupal\vactory_quiz\Services\VactoryQuizManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vactory quiz form class.
 */
class QuizForm extends FormBase {

  /**
   * Quiz manager service.
   *
   * @var \Drupal\vactory_quiz\Services\VactoryQuizManager
   */
  protected $quizManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\vactory_quiz\Services\VactoryQuizManager
   */
  protected $moduleHandler;

  /**
   * Queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Quiz Certificat settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $quizCertificatSettings;

  /**
   * Vactory devtools service.
   *
   * @var \Drupal\vactory_core\Services\VactoryDevTools
   */
  protected $vactoryDevTools;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Class constructor.
   */
  public function __construct(
    VactoryQuizManager $quizManager,
    ModuleHandlerInterface $moduleHandler,
    QueueFactory $queueFactory,
    VactoryDevTools $vactoryDevTools,
    RendererInterface $renderer
  ) {
    $this->quizManager = $quizManager;
    $this->moduleHandler = $moduleHandler;
    $this->queueFactory = $queueFactory;
    $this->quizCertificatSettings = $this->config('vactory_quiz_certificat.settings');
    $this->vactoryDevTools = $vactoryDevTools;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_quiz.manager'),
      $container->get('module_handler'),
      $container->get('queue'),
      $container->get('vactory_core.tools'),
      $container->get('renderer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_quiz_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $quiz = NULL) {
    // Get module configuration.
    $config = $this->config('vactory_quiz.settings');
    $is_parallel_correction_enabled = $config->get('parallel_correction');
    $show_correction = $config->get('show_quiz_correction');
    $allow_new_attempts = $config->get('allow_new_attempts');
    $allow_new_attempts_title = $config->get('allow_new_attempts_title');
    $next_button_title = $config->get('next_button_title');
    $validate_answer_title = $config->get('validate_answer_title');
    $current_user = \Drupal::currentUser();
    if (!isset($quiz)) {
      // No quiz case.
      return [
        '#markup' => $this->t('Aucun quiz trouvé'),
      ];
    }

    // If an attempt history patch the current user & quiz then get infos from.
    $attempt_history_exist = $form_state->get('attemptHistoryExist');
    $attempt_history_exist = !isset($attempt_history_exist) ? $quiz['attempt_history_exist'] : $attempt_history_exist;

    if ($attempt_history_exist) {
      // Set form information from user attempt history.
      $form_state->set('results', $quiz['results']);
      $form_state->set('questions', $quiz['questions']);
      $form_state->set('current_question_num', count($quiz['questions']) + 1);
      $form_state->set('perfectMark', $quiz['perfect_mark']);
    }

    $is_validate_answer = $form_state->get('isValidateAnswer');
    $form_state->set('entity_id', $quiz['entity_id']);

    if (empty($form_state->get('questions'))) {
      $form_state->set('questions', $quiz['questions']);
    }

    $questions = $form_state->get('questions');

    if (empty($form_state->get('results'))) {
      $quiz['results']['user_mark'] = 0;
      $form_state->set('results', $quiz['results']);
    }
    $results = $form_state->get('results');
    $ajax_wrapper_id = 'quiz-form--' . $quiz['entity_id'];
    $form['#attributes']['id'] = $ajax_wrapper_id;
    $form_state->set('questions', $questions);
    $questions_count = count($questions);
    if (empty($form_state->get('current_question_num'))) {
      $form_state->set('current_question_num', 1);
    }
    $current_question_number = $form_state->get('current_question_num');
    if ($current_question_number > $questions_count) {
      $perfect_mark = $form_state->get('perfectMark');
      $content = [
        'results' => $results,
        'perfect_mark' => $perfect_mark,
        'questions' => $questions,
        'show_correction' => $show_correction,
        'quiz_history' => $this->moduleHandler->moduleExists('vactory_quiz_history'),
      ];
      // Check if certificat is enabled in quiz then generate it when necessary.
      if (isset($quiz['certificat'])) {
        $min_required_result = $quiz['certificat']['min_required_result'];
        $user_perfect_mark = (int) $results['perfect_mark'];
        $user_result_percentage = intval(round(($user_perfect_mark*100)/(int) $perfect_mark));
        if ($user_result_percentage >= $min_required_result) {
          $generate_certificat_method = $this->quizCertificatSettings->get('method');
          if ($generate_certificat_method === 'html2pdf') {
            $content['enable_email'] = $this->quizCertificatSettings->get('enable_email');
            $content['certificat_url'] = $this->generateCertificatUsingMpdfQueue($results, $form_state, $quiz);
          }
          else {
            $content['certificat_print_url'] = $this->generateCertificateUsingBrowserPrint($results, $current_user, $quiz);
          }
        }
      }
      // Render Quiz Results.
      $form['quiz_results'] = [
        '#theme' => 'vactory_quiz_results',
        '#content' => $content,
      ];
      if ($allow_new_attempts) {
        $form['new_attempt'] = $this->getSubmitElement($form, $form_state, $ajax_wrapper_id, $allow_new_attempts_title);
      }
      return $form;
    }
    $current_question = $questions[$current_question_number - 1];
    $current_question_answers = $current_question['question_answers'];
    if ($is_validate_answer) {
      $form['validated_answer'] = [
        '#theme' => 'quiz_validate_answer',
        '#content' => [
          'user_answers' => $results['user_answers'],
          'question' => $current_question,
        ],
      ];
      $form['next'] = $this->getSubmitElement($form, $form_state, $ajax_wrapper_id, $next_button_title);
      return $form;
    }
    $options = [];
    foreach ($current_question_answers as $question_answer) {
      $option = [
        '#theme' => 'quiz_answer_option',
        '#content' => $question_answer,
      ];
      $options[$question_answer['answer_id']] = $this->renderer->renderPlain($option);
    }
    $form['question_body'] = [
      '#markup' => '<div class="d-flex mb-3"><strong class="mx-2">' . $current_question['question_number'] . '.</strong><div>' . $current_question['question_text_value'] . '</div></div>',
      '#prefix' => '<div class="shadow p-3 mb-5 mt-4 bg-white rounded">',
    ];
    $form['user_answer'] = [
      '#type' => $current_question['question_type'] === 'multiple' ? 'checkboxes' : 'radios',
      '#options' => $options,
      '#suffix' => '</div>',
    ];

    if ($is_parallel_correction_enabled) {
      $form['validate'] = $this->getSubmitElement($form, $form_state, $ajax_wrapper_id, $validate_answer_title);
    }

    if (!$is_parallel_correction_enabled || $is_validate_answer) {
      $form['next'] = $this->getSubmitElement($form, $form_state, $ajax_wrapper_id, $next_button_title);
    }

    $form['#attached']['library'][] = 'vactory_quiz/vactory_quiz_style';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $action = end($triggering_element['#parents']);
    $is_validate_answer = $form_state->get('isValidateAnswer');
    $current_question_num = $form_state->get('current_question_num');
    $questions = $form_state->get('questions');
    $current_user = \Drupal::currentUser();
    $results = $form_state->get('results');
    $entity_id = $form_state->get('entity_id');
    if ($action === 'validate' || ($action === 'next' && !$is_validate_answer)) {
      $index = $current_question_num - 1;
      $current_question = $questions[$index];
      $correct_answers = array_filter($current_question['question_answers'], function ($answer) {
        return $answer['is_correct'];
      });
      $correct_answers = array_map(function ($answer) {
        return $answer['answer_id'];
      }, $correct_answers);
      $user_answer = $form_state->getValue('user_answer');
      $user_answer = !is_array($user_answer) ? (!empty($user_answer) ? [$user_answer] : []) : $user_answer;
      $user_answer = array_filter($user_answer, function ($answer) {
        return $answer !== 0;
      });
      $user_answer = array_values($user_answer);
      $user_score = isset($results['user_mark']) ? $results['user_mark'] : 0;
      if (
        !empty($user_answer) &&
        count($user_answer) === count($correct_answers) &&
        count(array_intersect($user_answer, $correct_answers)) === count($correct_answers)) {
        $user_score += (int) $current_question['question_reward'];
      }
      else {
        $user_score -= (int) $current_question['question_penalty'];
      }

      // Set User best score.
      if (!isset($results['perfect_mark']) || $results['perfect_mark'] < $user_score) {
        $results['perfect_mark'] = $user_score;
      }

      // Set user last score.
      $results['user_mark'] = $user_score;
      $results['user_answers'][$current_question_num] = $user_answer;
      $perfect_mark = $this->quizManager->getPerfectMark($entity_id);
      $form_state->set('results', $results);
      $form_state->set('perfectMark', $perfect_mark);
      $form_state->set('isValidateAnswer', TRUE);
    }

    if ($action === 'next') {
      if ($current_question_num === count($questions) && $this->moduleHandler->moduleExists('vactory_quiz_history')) {
        $this->quizManager->updateUserAttemptHistory($current_user->id(), $entity_id, $results['user_mark'], $results['user_answers'], $results['certificat']);
      }
      $form_state->set('isValidateAnswer', FALSE);
      $form_state->set('current_question_num', $current_question_num + 1);
    }

    if ($action === 'new_attempt') {
      $results = $form_state->get('results');
      $results = array_intersect_key($results, array_flip(['perfect_mark', 'certificat']));
      // In new attempt case we empty all form data.
      $form_state->set('results', $results);
      $form_state->set('isValidateAnswer', NULL);
      $form_state->set('current_question_num', NULL);
      $form_state->set('attemptHistoryExist', FALSE);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Update form ajax callback.
   */
  public function updateFormCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Get submit form element.
   */
  public function getSubmitElement(array &$form, FormStateInterface $form_state, $ajax_wrapper_id, $title) {
    return [
      '#type' => 'submit',
      '#value' => $title,
      '#ajax' => [
        'callback' => [$this, 'updateFormCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
  }

  /**
   * Generate certificat using MPDF.
   */
  public function generateCertificatUsingMpdfQueue(&$results, $form_state, $quiz) {
    $certificat_url = NULL;
    if (isset($results['certificat']) && !empty($results['certificat']) && file_exists($results['certificat'])) {
      // Certificat already generated so get related uri from history.
      $certificat_url = \Drupal::service('file_url_generator')->generateAbsoluteString($results['certificat']);
    }
    elseif (
      !isset($results['certificat']) ||
      (isset($results['certificat']) && $results['certificat'] !== 1)
    ) {
      // Certificate not yet generated so add item to quiz certificat.
      // Queue processor so the certificat will be generated later.
      $certificat_info = \Drupal::service('vactory_quiz_certificat.manager')->getCertificatInfos($quiz['entity_id']);
      if (is_array($certificat_info)) {
        $certificat_queue_processor = $this->queueFactory->get('quiz_certificat_queue_processor');
        $certificat_queue_processor->createItem([
          'html_output' => $certificat_info['html_output'],
          'output_file' => $certificat_info['output_file'],
          'mpdf_options' => $certificat_info['mpdf_options'],
          'quiz_id' => $quiz['entity_id'],
          'user_id' => \Drupal::currentUser()->id(),
          'user_mark' => $results['user_mark'],
          'user_answers' => $results['user_answers'],
        ]);
        // 1 means certificate in progress.
        $results['certificat'] = 1;
        $certificat_url = 1;
        $form_state->set('results', $results);
        $this->quizManager->updateUserAttemptHistory(
          \Drupal::currentUser()->id(),
          $quiz['entity_id'],
          $results['user_mark'],
          $results['user_answers'],
          1,
          $results['certificat_time'],
        );
      }
    }
    return $certificat_url;
  }

  /**
   * Generate certificat using browser print.
   */
  public function generateCertificateUsingBrowserPrint(&$results, $current_user, $quiz) {
    if (!isset($results['certificat']) || $results['certificat'] !== 2 || !isset($results['certificat_time'])) {
      // 2 means get certificat using browser print.
      $results['certificat'] = 2;
      $results['certificat_time'] = \Drupal::time()->getCurrentTime();
      $this->quizManager->updateUserAttemptHistory(
        \Drupal::currentUser()->id(),
        $quiz['entity_id'],
        $results['user_mark'],
        $results['user_answers'],
        $results['certificat'],
        $results['certificat_time'],
      );
    }
    $token = $current_user->id() . '_' . $quiz['entity_id'] . '_' . $results['certificat_time'];
    $token = $this->vactoryDevTools->encrypt($token);
    return Url::fromRoute('vactory_quiz_certificat.generate', ['token' => $token]);
  }

}
