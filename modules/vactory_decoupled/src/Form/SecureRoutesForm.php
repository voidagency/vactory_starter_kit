<?php

namespace Drupal\vactory_decoupled\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\locale\SourceString;

/**
 * Add protected routes.
 *
 * @package Drupal\vactory_decoupled\Form
 */
class SecureRoutesForm extends ConfigFormBase
{

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames()
  {
    return ['vactory_decoupled.settings'];
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
  public function getFormId()
  {
    return 'vactory_decoupled_secure_routes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('vactory_decoupled.settings');

    $form['routes'] = [
      '#type' => 'textarea',
      '#title' => t('Routes'),
      '#default_value' => $config->get('routes'),
      '#description' => t("Enter one value per line. <b>E.g</b>: /en/api/user/register"),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->config('vactory_decoupled.settings')
      ->set('routes', $form_state->getValue('routes'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
