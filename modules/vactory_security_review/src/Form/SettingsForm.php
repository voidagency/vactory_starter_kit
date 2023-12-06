<?php

namespace Drupal\vactory_security_review\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\security_review\Form\SettingsForm as SecurityReviewSettings;

/**
 * Configure Vactory Security Review settings for this site.
 */
class SettingsForm extends SecurityReviewSettings {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $check_settings = $this->config('security_review.checks');
    $form['logger'] = [
      '#type' => 'details',
      '#title' => $this->t('Log messages'),
    ];
    $form['logger']['log_failed_auth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log failed authentication attempts'),
      '#default_value' => $check_settings->get('log_failed_auth'),
    ];
    $form['logger']['log_user_privileges_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log user privileges change (roles and/or permissions)'),
      '#default_value' => $check_settings->get('log_user_privileges_change'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('security_review.checks')
      ->set('log_failed_auth', $form_state->getValue('log_failed_auth'))
      ->set('log_user_privileges_change', $form_state->getValue('log_user_privileges_change'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
