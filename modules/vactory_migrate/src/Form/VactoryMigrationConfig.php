<?php

namespace Drupal\vactory_migrate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class VactoryMigrationConfig extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['vactory_migrate.settings'];
  }

  public function getFormId() {
    return 'vactory_migrate_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_migrate.settings');
    $delimiter = $config->get('delimiter');
    $batch_size = $config->get('batch_size');

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

    return $form;

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_migrate.settings');
    $config->set('delimiter', $form_state->getValue('delimiter'))
      ->set('batch_size', $form_state->getValue('batch_size'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}