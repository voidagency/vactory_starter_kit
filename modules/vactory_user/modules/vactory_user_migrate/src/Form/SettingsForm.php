<?php

namespace Drupal\vactory_user_migrate\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Configure Vactory User Migrate settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_user_migrate_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_user_migrate.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_user_migrate.settings');
    $form['csv'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload users CSV file.'),
      '#default_value' => !empty($config->get('csv')) ? $config->get('csv') : '',
      '#upload_location'   => 'private://user-migrate',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#description' => t("Charger un fichier csv contenant la liste des utilisateur à importer via migrate plugin") . ' <a href="/profiles/contrib/vactory_starter_kit/modules/vactory_user/modules/vactory_user_migrate/artifacts/users.csv">' . t('Télécharger le modèle CSV') . '</a>.',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['csv']) && empty($values['csv'])) {
      $form_state->setErrorByName('csv', $this->t("No file has been chosen"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = reset($form_state->getValue('csv'));
    $file = File::load($fid);
    $file->setPermanent();
    $file->save();

    $new_filename = "user-migrate/users.csv";
    $stream_wrapper = \Drupal::service('stream_wrapper_manager')->getScheme($file->getFileUri());
    $new_filename_uri = "{$stream_wrapper}://{$new_filename}";
    $file = \Drupal::service('file.repository')->move($file, $new_filename_uri, FileSystemInterface::EXISTS_REPLACE);
    // Save configuration settings.
    $this->config('vactory_user_migrate.settings')
      ->set('csv', [$file->id()])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
