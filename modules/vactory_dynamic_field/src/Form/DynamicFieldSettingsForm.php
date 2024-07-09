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
      '#default_value' => $this->config('vactory_dynamic_field.settings')
        ->get('is_dropdown_select_templates'),
      '#description' => t('Uncheck it to have a templates listing with thumbnail.'),
    ];

    $form['pending_content'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable pending static content feature.'),
      '#default_value' => $this->config('vactory_dynamic_field.settings')
        ->get('pending_content'),
    ];

    $form['auto_populate'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable auto populate feature.'),
      '#default_value' => $this->config('vactory_dynamic_field.settings')
        ->get('auto_populate'),
    ];

    $form['all_category'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable "ALL" Category for DF.'),
      '#default_value' => $this->config('vactory_dynamic_field.settings')->get('all_category'),
    ];

    $form['decoupled_edit_live_mode'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable decoupled edit live mode.'),
      '#default_value' => $this->config('vactory_dynamic_field.settings')->get('decoupled_edit_live_mode') ?? FALSE,
    ];

    $form['excluded_widgets'] = [
      '#type' => 'textarea',
      '#title' => t('Excluded widgets'),
      '#attributes' => ['data-yaml-editor' => 'true'],
      '#default_value' => $this->config('vactory_dynamic_field.settings')
        ->get('excluded_widgets'),
      '#description' => 'You can check this <a target="_blank" href="https://gist.githubusercontent.com/khalidbouhouch77/156d2f52b6d6d7c10712a5250326da12/raw/4dd765097dbc365f6b16348d827ee4f4145ac1f8/disble-dynamic-fields-examples.txt">link</a> to see some examples.',
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
      ->set('pending_content', $form_state->getValue('pending_content'))
      ->set('auto_populate', $form_state->getValue('auto_populate'))
      ->set('all_category', $form_state->getValue('all_category'))
      ->set('decoupled_edit_live_mode', $form_state->getValue('decoupled_edit_live_mode'))
      ->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
