<?php

namespace Drupal\vactory_redirect\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Configure vactory_redirect settings for this site.
 */
class UploadUrlsCsv extends ConfigFormBase {

  /**
   * String Config settings.
   */
  const SETTINGS = 'vactory_redirect.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_redirect_settings';
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
    $config = $this->config('vactory_redirect.settings');
    $form['redirect_file'] = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('Upload redirect CSV file.'),
      '#default_value' => !empty($config->get('redirect_file')) ? $config->get('redirect_file') : '',
      '#upload_location'   => 'public://redirections',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#description' => t("Charger un fichier csv contenant une liste des redirection 301 souhaitées") . ' <a href="/profiles/contrib/vactory_starter_kit/modules/vactory_redirect/examples/file.csv">' . t('Télécharger le modèle CSV') . '</a>.',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['redirect_file']) && empty($values['redirect_file'])) {
      $form_state->setErrorByName('redirect_file', $this->t("No file chosen"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $fid = reset($form_state->getValue('redirect_file'));
    $file = File::load($fid);
    $file->setPermanent();
    $file->save();

    $new_filename = "redirections/urls.csv";
    $stream_wrapper = \Drupal::service('stream_wrapper_manager')->getScheme($file->getFileUri());
    $new_filename_uri = "{$stream_wrapper}://{$new_filename}";
    $file = \Drupal::service('file.repository')->move($file, $new_filename_uri, FileSystemInterface::EXISTS_REPLACE);
    // Save configuration settings.

    $this->config('vactory_redirect.settings')
      ->set('redirect_file', [$file->id()])
      ->save();

    apcu_delete('redirection_csv');

    parent::submitForm($form, $form_state);
  }

}
