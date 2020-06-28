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
    $form['appointment_hours_string'] = [
      '#type' => 'textarea',
      '#title' => t('Appointment hours'),
      '#description' => t('List of <strong>Key|Value</strong> for authorized hours to use within an appointment, Exple: 8h_9h|8H - 9H'),
      '#default_value' => $config->get('appointment_hours_string'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $hours_string = $form_state->getValue('appointment_hours_string');
    $hours_string = preg_split('/\n|\r\n?/', $hours_string);
    $appointment_hours = [];
    if (!empty($hours_string)) {
      foreach ($hours_string as $appointment_hour) {
        $appointment_hour = explode('|', $appointment_hour);
        $appointment_hours[$appointment_hour[0]] = $appointment_hour[1];
      }
    }
    $config = $this->config('vactory_appointment.settings');
    $config->set('appointment_hours_string', $form_state->getValue('appointment_hours_string'))
      ->set('appointment_hours', $appointment_hours)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
