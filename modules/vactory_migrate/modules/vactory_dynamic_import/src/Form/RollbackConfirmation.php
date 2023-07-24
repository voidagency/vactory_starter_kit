<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rollback Confirmation.
 */
class RollbackConfirmation extends FormBase {

  /**
   * Rollback Service.
   *
   * @var \Drupal\vactory_migrate\Services\Import
   */
  protected $rollbackService;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->rollbackService = $container->get('vactory_migrate.rollback');
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
    return 'vactory_dynamic_import.rollback_confirmation';
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

    $message = $this->t("The rollback will be performed after clicking the 'start rollback' button.<br> Choose related migrations to rollback <br>");

    $rollback_key = \Drupal::request()->query->get('rollback');
    $migration_id = \Drupal::request()->query->get('migration');

    $config_id = 'migrate_plus.migration.' . $rollback_key;

    $sql = "SELECT name FROM config ";
    $sql .= "WHERE  name LIKE :name";
    $results = \Drupal::database()
      ->query($sql, [':name' => $config_id . '%'])
      ->fetchAllAssoc('name', \PDO::FETCH_ASSOC);

    $related_migrations = $results;

    $fornatted = array_map(function ($item) use ($migration_id) {
      return $item['name'];
    }, $related_migrations);

    $migrations_rollback = array_filter($fornatted, function ($item) use ($migration_id) {
      return !str_ends_with($item, $migration_id);
    });

    $form['message'] = [
      '#markup' => '<p>' . $message . '</p>',
    ];

    $form['rollbacks'] = [
      '#type'    => 'checkboxes',
      '#options' => $migrations_rollback,
    ];

    $form['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t("Start Rollback"),
      '#button_type' => 'primary',
    ];

    $form['cancel'] = [
      '#type'                    => 'submit',
      '#value'                   => t('Cancel'),
      '#submit'                  => ['::cancel'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $migration_id = \Drupal::request()->query->get('migration');
    $rollback_key = \Drupal::request()->query->get('rollback');
    if (!isset($migration_id) || !isset($rollback_key)) {
      $form_state->setErrorByName('submit', $this->t('Rollback is currently unavailable.'));
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
    $migration_id = \Drupal::request()->query->get('migration');

    $rollbacks = $form_state->getValue('rollbacks');

    $rollbacks_checked = array_filter($rollbacks, function ($item) {
      return $item != 0;
    });

    foreach ($rollbacks_checked as $migration) {
      $split = $migration ? explode('.', $migration) : [];
      $id = end($split);
      if ($id != $migration_id) {
        $this->rollbackService->rollback($id);
        \Drupal::configFactory()->getEditable($migration)->delete();
      }
    }

    $url = Url::fromRoute('vactory_dynamic_import.import')->setRouteParameters(['migration' => $migration_id]);

    $form_state->setRedirectUrl($url);
  }

  /**
   * Cancel Action.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('vactory_dynamic_import.form');
  }

}
