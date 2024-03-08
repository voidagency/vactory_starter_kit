<?php

namespace Drupal\vactory_migrate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Vactory migrate global settings class.
 */
class VactoryMigrationConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_migrate.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_migrate_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_migrate.settings');
    $delimiter = $config->get('delimiter');
    $batch_size = $config->get('batch_size');
    $group = $config->get('group');

    $form['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delimiter'),
      '#description' => $this->t('The delimiter to be used to separate values in the CSV files.'),
      '#default_value' => isset($delimiter) ? $delimiter : '',
    ];

    $form['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch size'),
      '#description' => $this->t('Batch Size for Import and Rollback operations.'),
      '#default_value' => isset($batch_size) ? $batch_size : '',
    ];

    $groups = \Drupal::entityTypeManager()->getStorage('migration_group')
      ->loadMultiple();
    $groups = array_map(fn($group) => $group->label(), $groups);
    $current_path = \Drupal::service('path.current')->getPath();
    $link = Url::fromRoute('entity.migration_group.add_form', ['destination' => $current_path])
      ->toString(TRUE)
      ->getGeneratedUrl();
    $form['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Migration group'),
      '#options' => $groups,
      '#empty_option' => '- Select -',
      '#required' => TRUE,
      '#description' => $this->t('Select an existing migration group or <a href="@link">Create new migration group</a>', ['@link' => $link]),
      '#default_value' => isset($group) ? $group : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_migrate.settings');
    $config->set('delimiter', $form_state->getValue('delimiter'))
      ->set('batch_size', $form_state->getValue('batch_size'))
      ->set('group', $form_state->getValue('group'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
