<?php

namespace Drupal\vactory_quiz\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Quiz answers modal contriller class.
 */
class QuizAnswersModalFormController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritDoc}
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Controller callback.
   */
  public function openAnswersModalForm() {
    $response = new AjaxResponse();
    $question_number = \Drupal::request()->get('question_number');
    $title = $this->t('Question @num answers', ['@num' => $question_number]);

    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\vactory_quiz\Form\QuizAnswersModalForm');

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenDialogCommand('#quiz-answers-dialog-wrapper', $title, $modal_form, [
      'width' => '80%',
      'modal' => TRUE,
    ]));

    return $response;
  }

  /**
   * Access to the answers dialog: editors of quizzes only.
   *
   * The dialog is opened from the questions field of the quiz form.
   */
  public static function access(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      'create vactory_quiz content',
      'edit own vactory_quiz content',
      'edit any vactory_quiz content',
      'bypass node access',
    ], 'OR');
  }

}
