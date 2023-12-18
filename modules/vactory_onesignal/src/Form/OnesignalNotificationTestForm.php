<?php

namespace Drupal\vactory_onesignal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Onesignal notification test form.
 */
class OnesignalNotificationTestForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_onesignal_test';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification title'),
      '#required' => TRUE,
    ];
    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notification content'),
      '#required' => TRUE,
    ];
    $form['uids'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('User'),
      '#target_type' => 'user',
      '#validate_reference' => FALSE,
      '#tags' => TRUE,
      '#maxlength' => 60,
      '#description' => $this->t("Select concerned users (separated by comma ',') whenever you want to send notif to specific users"),
    ];
    $form['device_ids'] = [
      '#type' => 'textarea',
      '#title' => t('Device IDs'),
      '#description' => $this->t("Enter device ids whenever (subscription_ids) one per line whenever you want to send notif to specific device ids"),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate & send Notification'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $headings = [
      $langcode => $values['title'] ?? '',
    ];
    $contents = [
      $langcode => $values['content'] ?? '',
    ];
    $drupal_user_ids = [];
    if (isset($values['uids']) && !empty($values['uids'])) {
      $drupal_user_ids = array_map(fn($el) => $el['target_id'], $values['uids']);
    }
    $device_ids = [];
    if (!empty($values['device_ids'])) {
      $device_ids = explode("\n", $values['device_ids']);
      $device_ids = array_map(fn($el) => str_replace("\r", '', trim($el)), $device_ids);
    }

    // Send notification to onesignal push notifications system.
    \Drupal::service('vactory_onesignal.manager')->onesignalNotifyUsers($headings, $contents, '/fr', $drupal_user_ids, $device_ids);
  }

}
