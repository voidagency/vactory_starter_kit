<?php

namespace Drupal\vactory_sms_sender_example\Plugin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Vactory SMS Sender Example Form.
 */
class VactorySmsSenderExampleForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_sms_sender_example';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#description' => $this->t('A valid destination phone number with a valid prefix like "212" for Morocco country'),
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SMS message'),
      '#required' => TRUE,
      '#default_value' => $this->t('Hello world! This SMS has been sent by Vactory SMS Sender'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send SMS'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $to = $form_state->getValue('to');
    $message = $form_state->getValue('message');
    $sms_sender_manager = \Drupal::service('vactory_sms_sender.manager');
    $response = $sms_sender_manager->sendSms($to, $message);
    if ($response) {
      \Drupal::messenger()->addStatus($this->t('SMS has been sent successfully'));
    }
    else {
      \Drupal::messenger()->addError($this->t('An error occured while sending SMS, please check <a href="/admin/reports/dblog">Drupal recent logs</a> page for more details.'));
    }
  }

}
