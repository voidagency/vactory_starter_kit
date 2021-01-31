<?php

namespace Drupal\vactory_academy_agency\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide the formations setting form.
 *
 * @package Drupal\vactory_academy_agency\Form
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
    return ['vactory_academy_agency.settings'];
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
    return 'vactory_academy_agency_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_academy_agency.settings');
    $form = parent::buildForm($form, $form_state);
    $form['enable_email_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer les notifications par mail'),
      '#description' => $this->t('Uncheck it to disable sending email notifications.'),
      '#default_value' => $config->get('enable_email_notifications'),
    ];
    $form['formations_email_from'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Adresse mail expéditeur"),
      '#description' => $this->t('Leave empty to use default site email addresse.'),
      '#default_value' => $config->get('formations_email_from'),
      '#states' => [
        'invisible' => [
          ':input[name="enable_email_notifications"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['formation_datalayer'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Data layer settings'),
    ];
    $default_value = [
      'agency_academies' => 0,
      'first_name' => 0,
      'last_name' => 0,
      'phone' => 0,
      'email' => 0,
      'type_client' => 0,
    ];
    $form['formation_datalayer']['datalayer_concerned_fields'] = [
      '#type' => 'checkboxes',
      '#title' => 'Select the concerned fields.',
      '#options' => [
        'agency_academies' => $this->t('Formations'),
        'first_name' => $this->t('Prénom du client'),
        'first_name' => $this->t('Prénom du client'),
        'last_name' => $this->t('Nom du client'),
        'phone' => $this->t('Téléphone du client'),
        'email' => $this->t('Email du client'),
        'type_client' => $this->t('Type client'),
      ],
      '#default_value' => !empty($config->get('datalayer_concerned_fields')) ? $config->get('datalayer_concerned_fields') : $default_value,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_academy_agency.settings');
    $config->set('enable_email_notifications', $form_state->getValue('enable_email_notifications'))
      ->set('formations_email_from', $form_state->getValue('formations_email_from'))
      ->set('datalayer_concerned_fields', $form_state->getValue('datalayer_concerned_fields'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
