<?php

namespace Drupal\vactory_checklist\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Slack notifications.
 */
class SlackNotificationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_checklist_slack_notification_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_checklist.slack_notification'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_checklist.slack_notification');

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL du webhook Slack'),
      '#default_value' => $config->get('webhook_url') ?? '',
      '#maxlength' => 2048,
      '#description' => $this->t('URL du type Incoming Webhook Slack (voir https://api.slack.com/messaging/webhooks). Laisser vide pour désactiver l’envoi depuis le cron.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = trim((string) $form_state->getValue('webhook_url'));
    if ($url === '') {
      return;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('webhook_url', $this->t('Indiquez une URL valide.'));
      return;
    }
    if (stripos($url, 'https://hooks.slack.com/') !== 0) {
      $form_state->setErrorByName('webhook_url', $this->t('Les webhooks Slack entrants doivent commencer par https://hooks.slack.com/'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_checklist.slack_notification')
      ->set('webhook_url', trim((string) $form_state->getValue('webhook_url')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
