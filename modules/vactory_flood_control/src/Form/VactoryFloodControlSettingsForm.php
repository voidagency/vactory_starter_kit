<?php

namespace Drupal\vactory_flood_control\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flood_control\Form\FloodControlSettingsForm;

/**
 * Administration settings form.
 */
class VactoryFloodControlSettingsForm extends FloodControlSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $vactory_flood_control_config = $this->config('vactory_flood_control.settings');

    $form['notification'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification'),
    ];

    $form['notification']['emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email'),
      '#default_value' => $vactory_flood_control_config->get('emails') ?? '',
      '#description' => $this->t('Enter e-mails address that will be notified when a user is blocked. <br />Separated by comma (,). <br /> Example: admin@void.fr, dev@void.fr'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $vactory_flood_control_config = $this->configFactory->getEditable('vactory_flood_control.settings');
    $vactory_flood_control_config
      ->set('emails', $form_state->getValue('emails'))
      ->save();
  }

}
