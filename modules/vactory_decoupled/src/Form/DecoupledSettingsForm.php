<?php

namespace Drupal\vactory_decoupled\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/**
 * Add protected routes.
 *
 * @package Drupal\vactory_decoupled\Form
 */
class DecoupledSettingsForm extends ConfigFormBase
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
    $form = parent::buildForm($form, $form_state);

    $form['settings_tab'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['secure_routes'] = [
      '#type' => 'details',
      '#title' => $this->t('Secure routes'),
      '#group' => 'settings_tab',
    ];

    $form['cache_exclude'] = [
      '#type' => 'details',
      '#title' => $this->t('Frontend cache excludes'),
      '#group' => 'settings_tab',
    ];

    $form['auth_limit_access'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication limit access'),
      '#group' => 'settings_tab',
    ];

    $form['secure_routes']['routes'] = [
      '#type' => 'textarea',
      '#title' => t('Routes'),
      '#default_value' => $config->get('routes'),
      '#description' => t("Enter one value per line. <b>E.g</b>: /en/api/user/register"),
    ];

    $node_types = NodeType::loadMultiple();
    $node_types = array_map(fn($node_type) => $node_type->label(), $node_types);
    $form['cache_exclude']['cache_excluded_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded content type'),
      '#options' => $node_types,
      '#default_value' => $config->get('cache_excluded_types') ?? [],
      '#description' => $this->t("Nodes of selected content types will be excluded from frontend caching"),
    ];

    $user_roles = Role::loadMultiple();
    $user_roles = array_map(fn($role) => $role->label(), $user_roles);
    $form['auth_limit_access']['auth_roles_excluded'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded roles from authentication'),
      '#options' => $user_roles,
      '#default_value' => $config->get('auth_roles_excluded') ?? [],
      '#description' => $this->t("Roles will be excluded from front authentication"),
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_decoupled.settings')
      ->set('routes', $form_state->getValue('routes'))
      ->set('cache_excluded_types', array_filter($form_state->getValue('cache_excluded_types')))
      ->set('auth_roles_excluded', array_filter($form_state->getValue('auth_roles_excluded')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
