<?php

namespace Drupal\vactory_reminder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Vactory reminder settings form class.
 */
class VactoryReminderSettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_reminder.settings'];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_reminder_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_reminder.settings');
    $form['reminder_time_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Time limit'),
      '#description' => $this->t('Max execution time per cron for the reminder queue drush command (in seconds) default to 2 minutes'),
      '#default_value' => !empty($config->get('reminder_time_limit')) ? $config->get('reminder_time_limit') : 60 * 2,
      '#min' => 60,
    ];
    $form['reminder_lease_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Lease time'),
      '#description' => $this->t('After this lease expires, the item will be reset and another consumer can claim the item (in seconds) default to 5 minutes'),
      '#default_value' => !empty($config->get('reminder_time_limit')) ? $config->get('reminder_lease_time') : 60 * 5,
      '#min' => 60,
    ];
    $form['reminder_consumers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reminder consumers'),
      '#description' => ['#theme' => 'vactory_reminder_consumers_field_description'],
      '#default_value' => $config->get('reminder_consumers_string'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $reminder_consumers_string = $form_state->getValue('reminder_consumers');
    $now = time();
    if (!empty($reminder_consumers_string)) {
      $reminder_consumers_rows = explode(PHP_EOL, $reminder_consumers_string);
      if (!empty($reminder_consumers_rows)) {
        foreach ($reminder_consumers_rows as $index => $consumer_row) {
          $consumer = explode('|', $consumer_row);
          if (count($consumer) !== 2) {
            $form_state->setError($form, $this->t('Line @num should respect the format consumerId|dateIntervalString', ['@num' => $index + 1]));
          }
          else {
            $date_interval_string = trim($consumer[1]);
            if (!strtotime($date_interval_string, $now)) {
              $form_state->setErrorByName('reminder_consumers', $this->t('Line @num: @dateInterval is an invalid date interval string', [
                '@num' => $index + 1,
                '@dateInterval' => $date_interval_string,
              ]));
            }
          }
        }
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->configFactory->getEditable('vactory_reminder.settings');
    $reminder_consumers_string = $form_state->getValue('reminder_consumers');
    $consumers = [];
    if (!empty($reminder_consumers_string)) {
      $reminder_consumers_rows = explode(PHP_EOL, $reminder_consumers_string);
      if (!empty($reminder_consumers_rows)) {
        foreach ($reminder_consumers_rows as $consumer_row) {
          $consumer = explode('|', $consumer_row);
          if (count($consumer) === 2) {
            $consumers[trim($consumer[0])] = trim($consumer[1]);
          }
        }
      }
    }
    $config->set('reminder_consumers', $consumers)
      ->set('reminder_consumers_string', $reminder_consumers_string)
      ->set('reminder_lease_time', $form_state->getValue('reminder_lease_time'))
      ->set('reminder_time_limit', $form_state->getValue('reminder_time_limit'))
      ->save();
  }

}
