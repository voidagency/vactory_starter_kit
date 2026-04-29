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

    $form['slack_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer l’envoi des vérifications vers Slack (cron)'),
      '#default_value' => (bool) ($config->get('slack_enabled') ?? FALSE),
    ];

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL du webhook Slack'),
      '#default_value' => $config->get('webhook_url') ?? '',
      '#maxlength' => 2048,
      '#description' => $this->t('URL du type Incoming Webhook Slack (voir https://api.slack.com/messaging/webhooks). Laisser vide pour désactiver l’envoi depuis le cron.'),
    ];

    $form['notify_levels'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Niveaux à inclure dans la notification'),
      '#description' => $this->t('Seuls les résultats des vérifications correspondant aux niveaux cochés seront envoyés sur Slack lors du cron.'),
      '#options' => [
        'success' => $this->t('Succès'),
        'warning' => $this->t('Avertissement'),
        'error' => $this->t('Erreur'),
      ],
      '#default_value' => $config->get('notify_levels'),
    ];

    $form['include_details'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inclure les détails des vérifications dans Slack'),
      '#description' => $this->t('Si décoché, seuls le libellé et le message principal de chaque vérification sont envoyés (sans tableau de détails).'),
      '#default_value' => (bool) ($config->get('include_details') ?? TRUE),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = trim((string) $form_state->getValue('webhook_url'));
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('webhook_url', $this->t('Indiquez une URL valide.'));
    }
    elseif (stripos($url, 'https://hooks.slack.com/') !== 0) {
      $form_state->setErrorByName('webhook_url', $this->t('Les webhooks Slack entrants doivent commencer par https://hooks.slack.com/'));
    }

    $levels = $form_state->getValue('notify_levels') ?? [];
    if (!array_filter($levels)) {
      $form_state->setErrorByName('notify_levels', $this->t('Cochez au moins un niveau à inclure dans la notification.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_checklist.slack_notification')
      ->set('slack_enabled', (bool) $form_state->getValue('slack_enabled'))
      ->set('webhook_url', trim((string) $form_state->getValue('webhook_url')))
      ->set('notify_levels', $form_state->getValue('notify_levels'))
      ->set('include_details', (bool) $form_state->getValue('include_details'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
