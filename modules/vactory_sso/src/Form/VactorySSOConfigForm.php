<?php

namespace Drupal\vactory_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VactorySSOConfigForm.
 *
 * @package Drupal\vactory_sso\Form
 */
class VactorySSOConfigForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_sso.settings'];
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
    return 'vactory_sso_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_sso.settings');

    $form['id_proverders_tabs'] = [
      '#type' => 'vertical_tabs',
    ];
    $form['keycloak_mapping'] = [
      '#type' => 'details',
      '#title' => t('Keycloak attributes mapping'),
      '#group' => 'id_proverders_tabs',
    ];

    for ($i = 0; $i < $config->get('max_attr_count'); $i++) {
      $form['keycloak_mapping']['vactory_sso_drupal_user_attr_' . ($i + 1)] = [
        '#type' => 'textfield',
        '#title' => t('Drupal user attribute name @index', ['@index' => $i + 1]),
        '#default_value' => !empty($config->get('vactory_sso_drupal_user_attr_' . ($i + 1))) ? $config->get('vactory_sso_drupal_user_attr_' . ($i + 1)) : '',
      ];
      $form['keycloak_mapping']['vactory_sso_keycloak_user_attr_' . ($i + 1)] = [
        '#type' => 'textfield',
        '#title' => t('Keycloak user attribute name @index', ['@index' => $i + 1]),
        '#default_value' => !empty($config->get('vactory_sso_keycloak_user_attr_' . ($i + 1))) ? $config->get('vactory_sso_keycloak_user_attr_' . ($i + 1)) : '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_sso.settings');
    for ($i = 0; $i < $config->get('max_attr_count'); $i++) {
      $config->set('vactory_sso_keycloak_user_attr_' . ($i + 1), $form_state->getValue('vactory_sso_keycloak_user_attr_' . ($i + 1)))
        ->set('vactory_sso_drupal_user_attr_' . ($i + 1), $form_state->getValue('vactory_sso_drupal_user_attr_' . ($i + 1)));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
