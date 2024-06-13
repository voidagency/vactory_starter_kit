<?php

namespace Drupal\vactory_page_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports pages (with DFs as excel).
 */
class Export extends FormBase {

  /**
   * Page import helpers service.
   *
   * @var \Drupal\vactory_page_import\Services\PageExportService
   */
  protected $pageExportService;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pageExportService = $container->get('vactory_page_import.export_helpers');
    return $instance;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_page_import.export';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $sql = "SELECT nid, title from node_field_data ";
    $sql .= "WHERE  type = 'vactory_page' AND default_langcode = 1";

    $query = \Drupal::database()->query($sql);
    $result = $query->fetchAll(\PDO::FETCH_KEY_PAIR);
    $normalized = [];
    foreach ($result as $nid => $title) {
      $normalized[$nid] = [
        'nid' => $nid,
        'title' => $title,
      ];
    }

    $form['pages'] = [
      '#type' => 'tableselect',
      '#header' => ['nid' => 'ID', 'title' => 'PAGE'],
      '#options' => $normalized,
      '#empty' => $this
        ->t('No data found'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Export pages"),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pages = $form_state->getValue('pages');
    $pages = array_filter($pages, function ($item) {
      return $item !== 0;
    });
    $available_languages = \Drupal::languageManager()->getLanguages();
    $available_languages = array_filter($available_languages, function ($language) {
      return !$language->isDefault();
    });

    $pages_data = [];
    foreach ($pages as $page) {
      $node = Node::load($page);
      $title = $node->get('title')->value;
      $pages_data[$title]['original'] = $this->pageExportService->constructNodeArray($node, 'original');
      foreach (array_keys($available_languages) as $language) {
        if ($node->hasTranslation($language)) {
          $node_translation = $node->getTranslation($language);
          $pages_data[$title][$language] = $this->pageExportService->constructNodeArray($node_translation, $language);
        }
      }
    }
    $filapath = $this->pageExportService->createExcelFromArray($pages_data);
    if ($filapath) {
      $response = new BinaryFileResponse($filapath, 200, [], FALSE);
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "pages.xlsx");
      $response->deleteFileAfterSend(TRUE);
      $response->send();
    }
  }

  /**
   * Form submission validator.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pages = $form_state->getValue('pages');
    $pages = array_filter($pages, function ($item) {
      return $item !== 0;
    });
    if (empty($pages)) {
      $form_state->setErrorByName('pages', 'Veuillez choisir les pages à exporter');
    }
  }

}
