<?php

namespace Drupal\vactory_content_package\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\vactory_content_package\ContentPackageConstants;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Configure Vactory content package settings for this site.
 */
class ContentPackageDownloadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_content_package_download';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $archive_file_path = ContentPackageConstants::EXPORT_DES_FILES . '/' . ContentPackageConstants::ARCHIVE_FILE_NAME . '.zip';
    $exist = file_exists($archive_file_path);

    if (!$exist) {
      \Drupal::messenger()
        ->addWarning($this->t('Before proceeding with the download, ensure that you have exported the content package.'));
    }

    $form['download'] = [
      '#type' => 'submit',
      '#value' => $this->t("Download the zip file"),
      '#button_type' => 'primary',
      '#disabled' => !$exist,
    ];

    $form['message'] = [
      '#markup' => '<p>' . $this->t('Export the content package <a href=":export_url">here</a> before proceeding with the download.', [
          ':export_url' => Url::fromRoute('vactory_content_package.export')
            ->toString(),
        ]) . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $archive_file_path = ContentPackageConstants::EXPORT_DES_FILES . '/' . ContentPackageConstants::ARCHIVE_FILE_NAME . '.zip';
    // Set archive as the response and delete the temp file.
    $response = new BinaryFileResponse(\Drupal::service('file_system')
      ->realPath($archive_file_path), 200, [], FALSE);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      ContentPackageConstants::ARCHIVE_FILE_NAME . '.zip'
    );
    $response->deleteFileAfterSend(TRUE);
    $response->send();
  }

}
