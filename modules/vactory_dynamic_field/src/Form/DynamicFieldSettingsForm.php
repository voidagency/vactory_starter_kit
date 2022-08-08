<?php

namespace Drupal\vactory_dynamic_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide a setting form for vactory Dynamic Field module.
 */
class DynamicFieldSettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_dynamic_field.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_dynamic_field_form_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['is_dropdown_select_templates'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable dropdown select templates mode.'),
      '#default_value' => $this->config('vactory_dynamic_field.settings')->get('is_dropdown_select_templates'),
      '#description' => t('Uncheck it to have a templates listing with thumbnail.'),
    ];

    $form['excluded_widgets'] = [
      '#type' => 'textarea',
      '#title' => t('Excluded widgets'),
      '#attributes' => ['data-yaml-editor' => 'true'],
      '#default_value' => $this->config('vactory_dynamic_field.settings')->get('excluded_widgets'),
      '#description' => $this->t('You can check this video https://www.loom.com/share/33c314e4373f4ad395cc21908f10b7b5 to see how it works.')
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_dynamic_field.settings')
      ->set('is_dropdown_select_templates', $form_state->getValue('is_dropdown_select_templates'))
      ->set('excluded_widgets', $form_state->getValue('excluded_widgets'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
