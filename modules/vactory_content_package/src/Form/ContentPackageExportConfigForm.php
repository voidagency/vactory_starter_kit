<?php

namespace Drupal\vactory_content_package\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_content_package\ContentPackageConstants;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Configure Vactory content package settings for this site.
 */
class ContentPackageExportConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_content_package_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Start export config process"),
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
    $widgetManager = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');
    $widgets = $widgetManager->getModalWidgetsList();

    $file_path = ContentPackageConstants::EXPORT_DES_FILES . '/df-config.json';
    file_put_contents($file_path, json_encode($widgets, JSON_PRETTY_PRINT));

    $response = new BinaryFileResponse(\Drupal::service('file_system')
      ->realPath($file_path), 200, [], FALSE);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      'df-config.json'
    );
    $response->deleteFileAfterSend(TRUE);
    $response->send();
  }

}
