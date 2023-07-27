<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration import form.
 */
class ImportConfirmation extends FormBase {

  /**
   * Import Service.
   *
   * @var \Drupal\vactory_migrate\Services\Import
   */
  protected $importService;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->importService = $container->get('vactory_migrate.import');
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
    return 'vactory_migrate_ui.import_confirmation';
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

    $message = $this->t("The import will be performed after clicking the 'Start import' button. Are you ready?");
    $form['message'] = [
      '#markup' => '<p>' . $message . '</p>',
    ];

    $form['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t("Start import"),
      '#button_type' => 'primary',
    ];

    $form['cancel'] = [
      '#type'   => 'submit',
      '#value'  => t('Cancel'),
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * Form Validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $migration_id = \Drupal::request()->query->get('migration');
    if (!isset($migration_id)) {
      $form_state->setErrorByName('submit', $this->t('Import is currently unavailable.'));
    }
    parent::validateForm($form, $form_state);
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
    $query_params = \Drupal::request()->query->all();
    $migration_id = $query_params['migration'] ?? NULL;
    $delimiter = $query_params['delimiter'] ?? NULL;
    $this->importService->import($migration_id, $delimiter);
    $form_state->setRedirect('vactory_dynamic_import.form');
  }

  /**
   * Cancel action.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('vactory_dynamic_import.form');
  }

}
