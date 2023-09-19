<?php

namespace Drupal\vactory_rate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Console to show different content types.
 */
class AddEntityForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_rate.settings'];
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
    return 'vactory_rate_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $content_types = NodeType::loadMultiple();
    $options = [];

    $selected_content_types = $this->getDefaultSelectedContentTypes();

    foreach ($content_types as $content_type) {
      $options[$content_type->id()] = $content_type->label();
    }
    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Content Types'),
      '#options' => $options,
      '#default_value' => $selected_content_types,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_content_types = array_filter($form_state->getValue('content_types'));
    // Printing the selected content types.
    if (!empty($selected_content_types)) {
      $content_type_labels = [];
      foreach ($selected_content_types as $content_type_id) {
        $content_type = NodeType::load($content_type_id);
        $content_type_labels[] = $content_type->label();
      }
      $message = $this->t('Selected Content Types: @content_types', ['@content_types' => implode(', ', $content_type_labels)]);
      $this->messenger()->addMessage($message);
    }

    $config = $this->config('vactory_rate.settings');
    $config->set('content_types', $form_state->getValue('content_types'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  private function getDefaultSelectedContentTypes() {
    $config = $this->configFactory->get('vactory_rate.settings');
    $default_content_types = $config->get('content_types') ?: [];

    return array_filter($default_content_types);
  }

}
