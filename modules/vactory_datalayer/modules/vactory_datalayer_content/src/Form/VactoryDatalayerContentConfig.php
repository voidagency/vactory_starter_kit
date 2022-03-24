<?php

namespace Drupal\vactory_datalayer_content\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class VactoryDatalayerContentConfig extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_datalayer_content.settings'];
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
    return 'vactory_datalayer_content_config';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_datalayer_content.settings');


    $form['vactory_datalayer_content_config'] = [
      '#type'          => 'textarea',
      '#rows' => 30,
      '#title'         => $this->t('Vactory Datalayer Content Config'),
      '#default_value' => !empty($config->get('vactory_datalayer_content_config')) ? $config->get('vactory_datalayer_content_config') : '',
//      '#description'   => "[{\"token_name\": \"First name\",\"token_key\": \"first_name\",\"field_machine_name\": \"field_first_name\",\"cible\": \"value\"
//                           },{\"token_name\": \"Points\",\"token_key\": \"user_points\",\"field_machine_name\": \"field_user_points\",\"cible\": \"value\"}]",
    ];

    return $form;

  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_datalayer_content.settings');
    $config->set('vactory_datalayer_content_config', $form_state->getValue('vactory_datalayer_content_config'))
      ->save();

    Cache::invalidateTags(['token_info']);
    parent::submitForm($form, $form_state);
  }
}
