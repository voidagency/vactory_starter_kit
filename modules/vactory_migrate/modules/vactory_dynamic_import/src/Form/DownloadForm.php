<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileUrlGenerator;

/**
 * Download Existing Content.
 */
class DownloadForm extends FormBase {

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new DownloadForm.
   *
   * @param \Drupal\Core\File\FileUrlGenerator $file_url_generator
   *   The file URL generator service.
   */
  public function __construct(FileUrlGenerator $file_url_generator) {
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_dynamic_import.download_existing_content';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (isset($_SESSION['dynamic_export_file_download'])) {
      $file_uri = $_SESSION['dynamic_export_file_download'];
      unset($_SESSION['dynamic_export_file_download']);
      // Generate the absolute URL to the file.
      $download_url = $this->fileUrlGenerator->generateAbsoluteString($file_uri);

      // Add a link to the form to download the file.
      $form['download_link'] = [
        '#type' => 'link',
        '#title' => $this->t('Download File'),
        '#url' => Url::fromUri($download_url),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
          'download' => '',
        ],
      ];
    }

    $dynamic_import_id = \Drupal::request()->query->get('dynamic_import');
    if ($dynamic_import_id) {
      $form['go_back_link'] = [
        '#type' => 'link',
        '#title' => $this->t('Return back to dynamic import'),
        '#url' => Url::fromRoute('entity.dynamic_import.edit_form', ['dynamic_import' => $dynamic_import_id]),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
