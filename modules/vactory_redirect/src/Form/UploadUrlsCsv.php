<?php

namespace Drupal\vactory_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\Exception\DirectoryNotReadyException;

/**
 * Configure vactory_redirect settings for this site.
 */
class UploadUrlsCsv extends ConfigFormBase {

  const SETTINGS = 'vactory_redirect.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_redirect';
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
     $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload csv'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uri = "redirections/urls.csv";
    $examples = "profiles/contrib/vactory_starter_kit/modules/vactory_redirect/examples/file.csv";
    $destination = "private://redirections/" ;
    if (!file_exists($destination)) {
      mkdir('private://redirections/', 0777, true);
    }
    if (file_exists($uri)) {
      $urls_file = file_get_contents($uri);
      file_put_contents($examples, $urls_file);
    }
    $file = file_get_contents($examples);
    /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
    $fileRepository = \Drupal::service('file.repository');
    try {
      $return = $fileRepository->writeData($file,  $destination  . basename($uri) , FileSystemInterface::EXISTS_RENAME) ;
      if($return) {
        \Drupal::messenger()->addMessage($this->t('File uploaded successfully.'));
      }
      return $return;
    }
    catch (InvalidStreamWrapperException $e) {
      \Drupal::messenger()->addError(t('The data could not be saved because the destination is invalid. More information is available in the system log.'));
      return FALSE;
    }
    catch (DirectoryNotReadyException $e) {
      \Drupal::messenger()->addError(t('Destination directory is not ready : Either it does not exist, or is not writable.'));
      return FALSE;
    }
    parent::submitForm($form, $form_state);
  }

}
