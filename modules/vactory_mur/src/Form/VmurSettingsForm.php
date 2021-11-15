<?php

namespace Drupal\vactory_mur\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VmurSettingsForm Defines a form to configure factory mur settings.
 *
 * @package Drupal\vactory_mur\Form
 */
class VmurSettingsForm extends ConfigFormBase {

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
    return 'vactory_mur_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactorymur.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form = parent::buildForm($form, $form_state);
    $config = \Drupal::config('vactorymur.settings');
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    $content_type_List = [];
    foreach ($types as $type => $id) {
      $content_type_List[$id->id()] = $type;

    }

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('All content types'),
      '#default_value' => $config->get('content_types'),
      '#options' => $content_type_List,
      '#description' => t('On the specified content types, an vactory mur  option will be available and can be enabled while this type is being edited.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactorymur.settings');
    if (!empty($form_state->getValue('content_types'))) {
      $config->set('content_types', $form_state->getValue('content_types'));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
