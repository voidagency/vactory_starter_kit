<?php

namespace Drupal\vactory_locator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Configure example settings for this site.
 */
class MigrationSettingsForm extends ConfigFormBase {

  /**
   * String Config settings.
   */
  const SETTINGS = 'locator_migration.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locator_migration_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['locator_csv_file'] = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('Upload CSV file contain locator items.'),
      '#upload_location'   => 'private://locator-migrate-files',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get file object.
    $form_file = $form_state->getValue('locator_csv_file', 0);
    if (isset($form_file[0]) && !empty($form_file[0])) {
      // Load File.
      /** @var \Drupal\file\Entity\File $file */
      $file = File::load($form_file[0]);
      $file->setPermanent();
      if ($file->save() > 0) {
        // Retrieve the configuration.
        $this->configFactory->getEditable(static::SETTINGS)
          // Set the submitted configuration setting.
          ->set('csv_file_path', $file->getFileUri())
          ->save();
        // Clear migration cache let hook_migration_plugins_alter take effect.
        // @see: vactory_locator.module.
        \Drupal::service('plugin.manager.migration')->clearCachedDefinitions();
      }
    }
    parent::submitForm($form, $form_state);
  }

}
