<?php

namespace Drupal\vactory_timeout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\vactory_timeout\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Function buildForm.
   *
   * @param array $form
   *   Array given.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Second param is FormStateInterface param.
   *
   * @return array
   *   An array returned
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_timeout.settings');

    $form['timeout'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Set the timeout'),
      '#default_value' => $config->get('timeout'),
      '#description'   => $this->t("Permet de définir le temps d'inactivité nécessaire pour retourner vers la page de veille (en secondes)"),
    ];

    $form['landing_page'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Landing page after timeout'),
      '#default_value' => $config->get('landing_page'),
      '#description'   => $this->t('Permet de définir le lien interne de la page de veille'),
    ];

    $form['paths'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Pages'),
      '#default_value' => $config->get('paths'),
      '#description'   => $this->t('Permet de définir les liens sur lesquels ce comportement ne sera pas appliqué. Chaque lien doit être spécifié sur une seule ligne'),
    ];

    $form['paths_authorisation'] = [
      '#type'          => 'radios',
      '#default_value' => $config->get('paths_authorisation'),
      '#options'       => [
        t('Unauthorize listed pages'),
        t('Authorize listed pages'),
      ],
    ];

    return $form;
  }

  /**
   * Function validateForm.
   *
   * @param array $form
   *   Array given.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Second param is FormStateInterface param.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // parent::validateForm($form, $form_state);.
  }

  /**
   * Function submitForm.
   *
   * @param array $form
   *   Array given.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Second param is FormStateInterface param.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('vactory_timeout.settings');
    if ($form_state->getValue('timeout') > 0 && $form_state->getValue('landing_page') !== "") {
      $config->set('timeout', $form_state->getValue('timeout'));
      $config->set('landing_page', $form_state->getValue('landing_page'));
      $config->set('paths', $form_state->getValue('paths'));
      $config->set('paths_authorisation', $form_state->getValue('paths_authorisation'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_timeout.settings',
    ];
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
    return 'vactory_timeout_form';
  }

}
