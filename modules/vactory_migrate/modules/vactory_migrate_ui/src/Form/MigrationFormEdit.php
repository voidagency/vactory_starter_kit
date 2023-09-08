<?php

namespace Drupal\vactory_migrate_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Migration form edit class.
 */
class MigrationFormEdit extends ConfigFormBase {

  /**
   * Build form function.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_params = \Drupal::request()->attributes->get('_route_params');
    if (isset($route_params)) {
      if (array_key_exists('migration', $route_params)) {
        $migration_id = $route_params['migration'];
        $data = \Drupal::config('migrate_plus.migration.' . $migration_id)->getRawData();
        unset($data['uuid']);
        unset($data['_core']);
        $yml = Yaml::encode($data);
        $form['config_migration'] = [
          '#type' => 'textarea',
          '#default_value' => $yml,
          '#title' => t('Configuration'),
          '#attributes' => ['data-yaml-editor' => 'true'],
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_params = \Drupal::request()->attributes->get('_route_params');
    $migration_id = $route_params['migration'];
    if (isset($route_params)) {
      if (array_key_exists('migration', $route_params)) {
        $config_migration = $form_state->getValue('config_migration');
        $data = Yaml::decode($config_migration);
        \Drupal::configFactory()
          ->getEditable('migrate_plus.migration.' . $migration_id)
          ->setData($data)
          ->save();
        drupal_flush_all_caches();
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Get editable config names function.
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_migrate_ui_settings',
    ];
  }

  /**
   * Get form id function.
   */
  public function getFormId() {
    return 'vactory_migrate_ui.settings';
  }

}
