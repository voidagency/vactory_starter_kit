<?php

namespace Drupal\vactory_content_package\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Environment;

/**
 * Configure Vactory content package settings for this site.
 */
class ContentPackageImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_content_package_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $url_options = ['absolute' => TRUE];
    $t_args = [
      ':settings_url' => Url::fromUri('base:/admin/structure/file-types/manage/document/edit', $url_options)
        ->toString(),
    ];
    $message = $this->t('If you\'re having trouble uploading the csv file. Add <strong><em>application/zip</em></strong> <a target="_blank" href=":settings_url"> to the allowed <em>MIME types</em></a>.', $t_args);

    $validators = [
      'file_validate_extensions' => ['zip'],
      'file_validate_size' => [Environment::getUploadMaxSize()],
    ];

    $form['upload'] = [
      '#type' => 'file',
      '#title' => $this->t('ZIP File'),
      '#description' => [
        '#theme' => 'file_upload_help',
        '#description' => $this->t("Load the zip file to import.<br>") . $message,
      ],
      '#upload_validators' => $validators,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Start process"),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $local_cache = NULL;
    if (!empty($_FILES['files']['name']['upload'])) {
      $validators = ['file_validate_extensions' => ['zip']];
      if (!($finfo = file_save_upload('upload', $validators, NULL, 0, FileSystemInterface::EXISTS_REPLACE))) {
        // Failed to upload the file. file_save_upload() calls
        // \Drupal\Core\Messenger\MessengerInterface::addError() on failure.
        return;
      }
      $local_cache = $finfo->getFileUri();
    }

    // Only execute the below if a file was uploaded.
    if (isset($local_cache)) {
      \Drupal::service('vactory_content_package.archiver.manager')
        ->unzipFile($local_cache);
    }

  }

}
