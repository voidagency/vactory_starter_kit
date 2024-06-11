<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration import form.
 */
class DynamicImportConfirmation extends FormBase {

  /**
   * Import service.
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
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_dynamic_import.import_confirmation';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $message = $this->t('The import will be performed after clicking the button. Are you ready?');
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
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $migration_id = \Drupal::request()->query->get('migration');
    if (!isset($migration_id)) {
      $form_state->setErrorByName('submit', $this->t('Import is currently unavailable.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migration_id = \Drupal::request()->query->get('migration');
    $this->importService->import($migration_id);
    $form_state->setRedirect('vactory_dynamic_import.execute', ['id' => 'migrate_plus.migration.' . $migration_id]);
  }

  /**
   * Cancel action.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $migration_id = \Drupal::request()->query->get('migration');
    $form_state->setRedirect('vactory_dynamic_import.execute', ['id' => 'migrate_plus.migration.' . $migration_id]);
  }

}
