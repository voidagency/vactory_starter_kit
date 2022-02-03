<?php

namespace Drupal\vactory_form_autosave\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Configure Vactory Form Autosave settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_form_autosave_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_form_autosave.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_form_autosave.settings');
    $form = parent::buildForm($form, $form_state);
    $form['policy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Policy'),
      '#options' => [
        0 => $this->t('Enable for listed forms'),
        1 => $this->t('Disable for listed forms'),
      ],
      '#default_value' => $config->get('policy'),
    ];
    $form['form_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Form IDs'),
      '#default_value' => $config->get('form_ids_string'),
      '#description' => $this->t('Enter one form id by line')
    ];
    $form['access'] = [
      '#type' => 'details',
      '#title' => $this->t('Access settings'),
    ];
    $roles = Role::loadMultiple();
    $roles = array_map(function ($role) {
      return $role->label();
    }, $roles);
    $form['access']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $roles,
      '#default_value' => $config->get('roles'),
      '#description' => $this->t('Select concerned roles, or leave empty if all roles are concerned')
    ];
    $form['lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Draft lifetime'),
      '#min' => 0,
      '#default_value' => $config->get('clean_draft_frequence'),
      '#description' => $this->t('Enter the lifetime of the draft in days, so the draft will be deleted after reaching the specified number of days since its creation, enter 0 for disabling draft deletion')
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_ids_string = $form_state->getValue('form_ids');
    $form_ids = explode(PHP_EOL, $form_ids_string);
    $form_ids = array_map(function ($form_id) {
      return trim(str_replace("\r", '', $form_id));
    }, $form_ids);
    $this->config('vactory_form_autosave.settings')
      ->set('form_ids', $form_ids)
      ->set('form_ids_string', $form_ids_string)
      ->set('policy', $form_state->getValue('policy'))
      ->set('roles', $form_state->getValue('roles'))
      ->set('lifetime', $form_state->getValue('lifetime'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
