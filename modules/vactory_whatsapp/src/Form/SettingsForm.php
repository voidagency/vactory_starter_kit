<?php

namespace Drupal\vactory_whatsapp\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Vactory Whatsapp settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_whatsapp_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_whatsapp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_whatsapp.settings');
    $token = $config->get('token');
    $business_accound_id = $config->get('business_accound_id');
    $options = [$this->t('No template has been found')];
    if (!empty($token) && !empty($business_accound_id)) {
      $uri = "https://graph.facebook.com/v14.0/${business_accound_id}/message_templates?access_token=${token}";
      $client = \Drupal::httpClient()->get($uri);
      $response = Json::decode($client->getBody()->getContents());
      if (isset($response['data'])) {
        $options = [];
        foreach ($response['data'] as $template) {
          $options[$template['name']] = "${template['name']} (${template['status']})";
        }
      }
    }

    $form['intro'] = [
      '#markup' => $this->t('Check create whatsapp business APP step by step guide:') . '<a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started">Get Started With the WhatsApp Business Cloud API</a>'
    ];
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Whatsapp business API permanent token'),
      '#default_value' => $config->get('token'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['phone_num_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From Phone number ID'),
      '#default_value' => $config->get('phone_num_id'),
      '#required' => TRUE,
      '#description' => 'Please visit your whatsapp business account to get the Phone num id on https://developers.facebook.com/apps/{APP_ID}/whatsapp-business/wa-dev-console'
    ];
    $form['business_accound_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Whatsapp business account ID'),
      '#default_value' => $config->get('business_accound_id'),
      '#required' => TRUE,
      '#description' => 'Please visit your whatsapp business account to get the Business account id on https://developers.facebook.com/apps/{APP_ID}/whatsapp-business/wa-dev-console'
    ];
    $form['template_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Default template ID'),
      '#options' => $options,
      '#default_value' => $config->get('template_id'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_whatsapp.settings')
      ->set('token', $form_state->getValue('token'))
      ->set('template_id', $form_state->getValue('template_id'))
      ->set('phone_num_id', $form_state->getValue('phone_num_id'))
      ->set('business_accound_id', $form_state->getValue('business_accound_id'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
