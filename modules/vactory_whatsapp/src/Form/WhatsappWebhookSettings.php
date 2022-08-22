<?php

namespace Drupal\vactory_whatsapp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_whatsapp\WhatsappWebhookManager;

class WhatsappWebhookSettings extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['vactory_whatsapp.settings'];
  }

  public function getFormId() {
    return 'vactory_whatsapp_webhook_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('vactory_whatsapp.settings');
    $whatsapp_webhooks_plugins = $config->get('whatsapp_webhook_plugins');
    $form = parent::buildForm($form, $form_state);
    $form['whatsapp_webhook_plugins'] = [
      '#type' => 'table',
      '#header' => [
        'enable' => $this->t('Enable'),
        'name' => $this->t('Plugin name'),
        'id' => $this->t('Plugin id'),
        'fields' => $this->t('Concerned fields'),
        'provider' => $this->t('Plugin provider'),
      ],
      '#tree' => TRUE,
    ];
    /** @var WhatsappWebhookManager $whatsapp_webhook_manager */
    $whatsapp_webhook_manager = \Drupal::service('plugin.manager.vactory_whatsapp_webhook');
    $definitions = $whatsapp_webhook_manager->getDefinitions();
    foreach ($definitions as $id => $definition) {
      $form['whatsapp_webhook_plugins'][$id]['enable'] = [
        '#type' => 'checkbox',
        '#default_value' => $whatsapp_webhooks_plugins[$id]['enable'] ?? 0,
      ];
      $form['whatsapp_webhook_plugins'][$id]['name'] = ['#markup' => $definition['label']];
      $form['whatsapp_webhook_plugins'][$id]['id'] = ['#markup' => $definition['id']];
      $form['whatsapp_webhook_plugins'][$id]['fields'] = ['#markup' => implode(', ', $definition['fields'])];
      $form['whatsapp_webhook_plugins'][$id]['provider'] = ['#markup' => $definition['provider']];
    }
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('vactory_whatsapp.settings');
    $config->set('whatsapp_webhook_plugins', $form_state->getValue('whatsapp_webhook_plugins'))
      ->save();
  }
}