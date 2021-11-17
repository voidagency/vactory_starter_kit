<?php

namespace Drupal\vactory_two_factors_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TwoFactorsOTPSettings.
 *
 * @package Drupal\vactory_two_factors_auth\Form
 */
class TwoFactorsOTPSettings extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_two_factors_auth.settings'];
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
    return 'vactory_two_factors_auth_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_two_factors_auth.settings');
    $form = parent::buildForm($form, $form_state);
    $form['mail_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Configurations Mail'),
    ];
    $form['sms_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Configurations SMS'),
    ];
    $form['roles_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
    ];
    $form['mail_settings']['mail_message_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sujet du mail'),
      '#default_value' => $config->get('mail_message_subject'),
    ];
    $form['mail_settings']['mail_message_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Corps du mail'),
      '#default_value' => $config->get('mail_message_body'),
      '#description' => $this->t('Le code OTP généré sera automatiquement ajouté à la fin de corps du mail.'),
    ];
    $form['sms_settings']['sms_message_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message SMS'),
      '#default_value' => $config->get('sms_message_body'),
      '#description' => $this->t('Le code OTP généré sera automatiquement ajouté à la fin de message.'),
    ];
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    $roles = array_map(function ($el) {
      return $el->label();
    }, $roles);
    $form['roles_settings']['concerned_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Sélectionner les rôles concernés'),
      '#options' => $roles,
      '#default_value' => $config->get('concerned_roles'),
      '#description' => $this->t('Si aucun choix alors tous les rôles sont concernés.'),
      '#multiple' => TRUE,
    ];
    $form['otp_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('La durée de vie du code OTP (En seconds)'),
      '#default_value' => $config->get('otp_lifetime'),
      '#description' => $this->t('Saisir 0 pour un OTP permanent.'),
      '#min' => 0,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('vactory_two_factors_auth.settings')
      ->set('mail_message_subject', $form_state->getValue('mail_message_subject'))
      ->set('mail_message_body', $form_state->getValue('mail_message_body')['value'])
      ->set('sms_message_body', $form_state->getValue('sms_message_body'))
      ->set('concerned_roles', $form_state->getValue('concerned_roles'))
      ->set('otp_lifetime', $form_state->getValue('otp_lifetime'))
      ->save();
  }

}
