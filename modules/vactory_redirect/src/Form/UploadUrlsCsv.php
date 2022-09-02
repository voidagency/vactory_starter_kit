<?php

namespace Drupal\vactory_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Configure example settings for this site.
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
    $form['redirect_file'] = [
      '#type'              => 'managed_file',
      '#title'             => $this->t('Upload redirect CSV file.'),
      '#default_value' => !empty($this->config('vactory_redirect.settings')->get('redirect_file')) ? $this->config('vactory_redirect.settings')->get('redirect_file') : '',
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

    // Save configuration settings.
    $this->config('vactory_redirect.settings')
      ->set('redirect_file', $values['redirect_file'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
