<?php

namespace Drupal\vactory_otp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Otp login config.
 *
 * Class VactoryOtpLoginConfig.
 */
class VactoryOtpLoginConfig extends ConfigFormBase {

  /**
   * Function get Editable Config Names.
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_otp.login_settings',
    ];
  }

  /**
   * Function Get Form Id.
   */
  public function getFormId() {
    return 'vactory_otp_login_settings';
  }

  /**
   * Function build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_otp.login_settings');

    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
    $fields = [];
    foreach ($field_definitions as $field_name => $field_definition) {
      $fields[$field_name] = "{$field_definition->getLabel()} ({$field_name})";
    }

    $form['login_field'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Login field'),
      '#options' => $fields,
      '#required' => TRUE,
      '#default_value' => $config->get('login_field'),
      '#description' => $this->t('The login field.'),
    ];

    $form['phone_field'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Phone field'),
      '#options' => $fields,
      '#empty_option' => '- Select -',
      '#default_value' => $config->get('phone_field'),
      '#description' => $this->t('The field specifying the phone number to receive the OTP.'),
    ];

    $form['email_field'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Email field'),
      '#options' => $fields,
      '#empty_option' => '- Select -',
      '#default_value' => $config->get('email_field'),
      '#description' => $this->t('The field specifying the email to receive the OTP.'),
    ];

    $form['expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('OTP Expiration (in seconds)'),
      '#required' => TRUE,
      '#default_value' => $config->get('expiration'),
      '#description' => $this->t('OTP validity duration.'),
    ];

    $form['canal'] = [
      '#type' => 'radios',
      '#options' => [
        'phone' => $this->t('Phone'),
        'email' => $this->t('Email'),
      ],
      '#title' => $this->t('Canal'),
      '#required' => TRUE,
      '#default_value' => $config->get('canal'),
      '#description' => $this->t('Token delivery method.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['canal'] == 'phone' && empty($values['phone_field'])) {
      $form_state->setErrorByName('phone_field', $this->t("Phone field is required since you selected 'phone' as the canal."));
    }
    if ($values['canal'] == 'email' && empty($values['email_field'])) {
      $form_state->setErrorByName('email_field', $this->t("Email field is required since you selected 'email' as the canal."));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Function Submittion Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_otp.login_settings');

    $config->set('login_field', $form_state->getValue('login_field'));
    $config->set('phone_field', $form_state->getValue('phone_field'));
    $config->set('email_field', $form_state->getValue('email_field'));
    $config->set('expiration', $form_state->getValue('expiration'));
    $config->set('canal', $form_state->getValue('canal'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
