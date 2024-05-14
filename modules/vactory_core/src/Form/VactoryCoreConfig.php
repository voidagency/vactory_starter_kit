<?php

namespace Drupal\vactory_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide form for global settings.
 */
class VactoryCoreConfig extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_core.global_config'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_core_global_config';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_core.global_config');
    $form['http_client_ssl_verification'] = [
      '#type' => 'checkbox',
      '#title' => t("Activer la vérification SSL pour httpClient"),
      '#default_value' => $config->get('http_client_ssl_verification') ?? TRUE,
    ];

    $form['generate_pdf_thumbnail_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this
        ->t('thumbnails des media de type PDF'),
    ];

    $form['generate_pdf_thumbnail_wrapper']['generate_pdf_thumbnail'] = [
      '#type' => 'checkbox',
      '#title' => t("Activer la création des thumbnails des media de type PDF"),
      '#default_value' => $config->get('generate_pdf_thumbnail') ?? FALSE,
    ];

    $form['generate_pdf_thumbnail_wrapper']['requirements'] = [
      '#type' => 'item',
      '#title' => t('Requirements Status'),
      '#markup' => $this->getRequirementsMarkup(),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $http_client_ssl_verification = $form_state->getValue('http_client_ssl_verification');
    $generate_pdf_thumbnail = $form_state->getValue('generate_pdf_thumbnail');
    $this->config('vactory_core.global_config')
      ->set('http_client_ssl_verification', $http_client_ssl_verification)
      ->set('generate_pdf_thumbnail', $generate_pdf_thumbnail)
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to get the markup for requirements status.
   */
  private function getRequirementsMarkup() {
    // Check if Imagick extension is loaded.
    $imagick_installed = extension_loaded('imagick');

    $output = '<div>';
    $output .= 'Imagick Extension: ' . ($imagick_installed ? 'Installed' : 'Not Installed');
    $output .= '</div>';

    return $output;
  }

}
