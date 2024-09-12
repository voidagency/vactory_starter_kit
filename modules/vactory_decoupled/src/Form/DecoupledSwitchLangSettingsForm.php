<?php

namespace Drupal\vactory_decoupled\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Decoupled switch lang settings form.
 */
class DecoupledSwitchLangSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_decoupled.switch_lang_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_decoupled_switch_lang_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_decoupled.switch_lang_settings');
    $form = parent::buildForm($form, $form_state);

    $form['hide_untranslated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show only languages with translations for current page'),
      '#default_value' => $config->get('hide_untranslated') ?? FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_decoupled.switch_lang_settings')
      ->set('hide_untranslated', $form_state->getValue('hide_untranslated'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
