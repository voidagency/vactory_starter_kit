<?php

namespace Drupal\vactory_report_content\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ReportedContentForm
 *
 * @package Drupal\vactory_report_content\Form
 */
class ReportContentSubmitForm extends FormBase {

  /**
   * entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritDoc}
   */
  /*public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    EntityRepositoryInterface $entityRepository,
    RendererInterface $renderer,
    AliasManagerInterface $aliasManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->entityRepository = $entityRepository;
    $this->renderer = $renderer;
    $this->aliasManager = $aliasManager;
  }*/

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->languageManager = $container->get('language_manager');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->renderer = $container->get('renderer');
    $instance->aliasManager = $container->get('path_alias.manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'report_content_submit';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $reasons = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'reported_content_reasons',
      ]);
    $options = array_map(function ($term) use ($langcode) {
      $translated_term = $this->entityRepository->getTranslationFromContext($term, $langcode);
      $render = [
        '#theme' => 'vactory_report_content_reason',
        '#reason' => $translated_term,
      ];
      return $this->renderer->render($render);
    }, $reasons);
    $status_messages = ['#type' => 'status_messages'];
    $form['#prefix'] .= $this->renderer->renderRoot($status_messages);
    $form['reason'] = [
      '#type' => 'radios',
      '#title' => $this->t('Why do you report this content ?'),
      '#options' => $options,
      '#required' => TRUE,
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment (optional)'),
      '#placeholder' => $this->t("Describe further why you are reporting this message..."),
    ];
    $form['submit_report'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'js-form-report-content'
      ],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $server = \Drupal::request()->server->all();
    $http_url = $server['HTTP_REFERER'];
    $http_url = parse_url($http_url);
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $path_alias = isset($http_url['path']) ? str_replace('/' . $langcode . '/', '/', $http_url['path']) : '/';
    $current_path = $this->aliasManager->getPathByAlias($path_alias);
    $reason = $form_state->getValue('reason');
    $description = $form_state->getValue('description');
    $status = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties([
        'name' => 'Active',
      ]);
    $status = !empty($status) ? reset($status)->id() : NULL;
    $values = [
      'page' => $current_path,
      'reason' => $reason,
      'description' => $description,
      'reporter' => \Drupal::currentUser()->id(),
    ];
    if ($status) {
      $values['status'] = $status;
    }
    $reported_content = $this->entityTypeManager->getStorage('reported_content')
      ->create($values);
    $reported_content->save();
  }

  /**
   * Ajax callback.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $errors = $form_state->getErrors();
    if (!empty($errors)) {
      $status_messages = ['#type' => 'status_messages'];
      $form['#prefix'] = '<div id="js-form-report-content">';
      $form['#prefix'] .= $this->renderer->renderRoot($status_messages);
      $form['#suffix'] = '</div>';
      return $form;
    }
    $ajax_response = new AjaxResponse();
    $content = [
      '#theme' => 'vactory_report_success_message',
    ];
    $ajax_response->addCommand(new ReplaceCommand('#js-form-report-content', $content));
    return $ajax_response;
  }

}
