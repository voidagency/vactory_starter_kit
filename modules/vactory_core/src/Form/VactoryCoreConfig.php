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

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $http_client_ssl_verification = $form_state->getValue('http_client_ssl_verification');
    $this->config('vactory_core.global_config')
      ->set('http_client_ssl_verification', $http_client_ssl_verification)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
