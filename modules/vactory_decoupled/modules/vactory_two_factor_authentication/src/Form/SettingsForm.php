<?php

namespace Drupal\vactory_two_factor_authentication\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Vactory Two Factor Authentication settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_two_factor_authentication_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_two_factor_authentication.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['type_2fa'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'mail' => t('Mail'),
        'phone' => t('Phone'),
      ],
      '#empty_option' => t('-- select --'),
      '#required' => TRUE,
      '#default_value' => $this->config('vactory_two_factor_authentication.settings')->get('type_2fa'),
    ];

    $form['cooldown'] = [
      '#type' => 'number',
      '#title' => $this->t('Code expiration (in seconds)'),
      '#required' => TRUE,
      '#default_value' => $this->config('vactory_two_factor_authentication.settings')->get('cooldown'),
    ];

    $form['max_attempt_per_login'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Attempt per login'),
      '#required' => TRUE,
      '#default_value' => $this->config('vactory_two_factor_authentication.settings')->get('max_attempt_per_login'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_two_factor_authentication.settings')
      ->set('cooldown', $form_state->getValue('cooldown'))
      ->set('max_attempt_per_login', $form_state->getValue('max_attempt_per_login'))
      ->set('type_2fa', $form_state->getValue('type_2fa'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
