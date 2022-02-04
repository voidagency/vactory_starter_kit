<?php

namespace Drupal\vactory_node_owner_override\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Vactory Node Owner Override settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_node_owner_override_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_node_owner_override.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_node_owner_override.settings');
    $form = parent::buildForm($form, $form_state);
    $form['policy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Policy'),
      '#options' => [
        0 => $this->t('Enable owner override for selected content types'),
        1 => $this->t('Disable owner override for selected content types'),
      ],
      '#default_value' => $config->get('policy'),
    ];
    $bundles = \Drupal::entityTypeManager()->getStorage('node_type')
      ->loadMultiple();
    $bundles = array_map(function ($bundle) {
      return $bundle->label();
    }, $bundles);
    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#options' => $bundles,
      '#default_value' => $config->get('bundles'),
      '#description' => $this->t('Select concerned bundles, Leave empty is equivalent to select all content types.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_node_owner_override.settings')
      ->set('policy', $form_state->getValue('policy'))
      ->set('bundles', $form_state->getValue('bundles'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
