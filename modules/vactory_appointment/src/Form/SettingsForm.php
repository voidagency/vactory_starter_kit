<?php

namespace Drupal\vactory_appointment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide the appointment setting form.
 *
 * @package Drupal\vactory_appointment\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_appointment.settings'];
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
    return 'vactory_appointment_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_appointment.settings');
    $form = parent::buildForm($form, $form_state);
    $form['is_authentication_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Seuls les utilisateurs authentifiés peuvent prendre des rendez-vous'),
      '#description' => $this->t("Si cochée les utilisateurs anonymes n'ont plus pu prise des rendez-vous"),
      '#default_value' => $config->get('is_authentication_required'),
    ];
    $form['user_can_edit_appointment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer la modification des rendez-vous'),
      '#description' => $this->t("Si cochée les utilisateurs peuvent modifier leurs rendez-vous"),
      '#default_value' => $config->get('user_can_edit_appointment'),
    ];
    $form['user_can_delete_appointment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer la suppression des rendez-vous'),
      '#description' => $this->t("Si cochée les utilisateurs peuvent supprimer leurs rendez-vous"),
      '#default_value' => $config->get('user_can_delete_appointment'),
    ];
    $form['enable_email_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer les notifications par mail'),
      '#description' => $this->t('Uncheck it to disable sending email notifications.'),
      '#default_value' => $config->get('enable_email_notifications'),
    ];
    $form['appointment_email_from'] = [
      '#type' => 'textfield',
      '#title' => t("Adresse mail expéditeur"),
      '#description' => $this->t('Leave empty to use default site email addresse.'),
      '#default_value' => $config->get('appointment_email_from'),
      '#states' => [
        'invisible' => [
          ':input[name="enable_email_notifications"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['appointment_datalayer'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Data layer settings'),
    ];
    $default_value = [
      'adviser' => 0,
      'first_name' => 0,
      'last_name' => 0,
      'phone' => 0,
      'email' => 0,
      'appointment_date' => 0,
    ];
    $form['appointment_datalayer']['datalayer_concerned_fields'] = [
      '#type' => 'checkboxes',
      '#title' => 'Select the concerned fields.',
      '#options' => [
        'adviser' => $this->t('Conseiller'),
        'first_name' => $this->t('Prénom du client'),
        'last_name' => $this->t('Nom du client'),
        'phone' => $this->t('Téléphone du client'),
        'email' => $this->t('Email du client'),
        'appointment_date' => $this->t('Date du rendez-vous'),
      ],
      '#default_value' => !empty($config->get('datalayer_concerned_fields')) ? $config->get('datalayer_concerned_fields') : $default_value,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $is_authentication_required = $form_state->getValue('is_authentication_required');
    if ($is_authentication_required) {
      $is_espace_prive_module_enabled = \Drupal::moduleHandler()->moduleExists('vactory_espace_prive');
      if (!$is_espace_prive_module_enabled) {
        $form_state->setErrorByName('is_authentication_required', t("Pour que l'authentification soit requise pour la prise de rendez-vous, il faut tout d'abord activer le module Vactory Espace Privé (vactory_espace_prive)"));
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_appointment.settings');
    $config->set('enable_email_notifications', $form_state->getValue('enable_email_notifications'))
      ->set('appointment_email_from', $form_state->getValue('appointment_email_from'))
      ->set('is_authentication_required', $form_state->getValue('is_authentication_required'))
      ->set('user_can_edit_appointment', $form_state->getValue('user_can_edit_appointment'))
      ->set('user_can_delete_appointment', $form_state->getValue('user_can_delete_appointment'))
      ->set('datalayer_concerned_fields', $form_state->getValue('datalayer_concerned_fields'))
      ->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
