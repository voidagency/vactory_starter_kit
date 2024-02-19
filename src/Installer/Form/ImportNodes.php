<?php

namespace Drupal\vactory_starter_kit\Installer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the site configuration form.
 */
class ImportNodes extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_decoupled_module_import_nodes';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['migrations'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getMigrationsList(),
      '#title' => $this->t('choose migration'),
    ];

    $form['#title'] = $this->t('Import nodes');
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start importing'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migrations = $form_state->getValue('migrations');
    $migrations_to_perform = [];
    foreach ($migrations as $migration) {
      if ($migration !== 0) {
        $migrations_to_perform[] = $migration;
      }
    }

    $build_info = $form_state->getBuildInfo();
    $install_state = $build_info['args'][0]['forms'];

    // Determine form state based off override existence.
    $install_state['form_state_values'] = isset($install_state['form_state_values'])
      ? $install_state['form_state_values']
      : [];
    $install_state['form_state_values'] += $form_state->getValues();

    $install_state['vactory_decoupled_import_nodes'] = $migrations_to_perform;

    $build_info['args'][0]['forms'] = $install_state;
    $form_state->setBuildInfo($build_info);
  }

  /**
   * Get list of csv migrations.
   */
  private function getMigrationsList() {
    $migration_configs = \Drupal::configFactory()
      ->listAll('migrate_plus.migration.');
    $migrations = [];
    foreach ($migration_configs as $migration_config) {
      $config = \Drupal::configFactory()->get($migration_config);
      $source = $config->get('source');
      if (isset($source) && array_key_exists('plugin', $source)) {
        if ($source['plugin'] == 'csv') {
          $migrations[$migration_config] = $config->get('label');
        }
      }
    }
    return $migrations;
  }

}
