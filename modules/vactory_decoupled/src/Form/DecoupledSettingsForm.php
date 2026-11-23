<?php

namespace Drupal\vactory_decoupled\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\vactory_decoupled\EventSubscriber\UserRegistrationRolesSubscriber;

/**
 * Add protected routes.
 *
 * @package Drupal\vactory_decoupled\Form
 */
class DecoupledSettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
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
  public function getFormId() {
    return 'vactory_decoupled_secure_routes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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

    $form['iframe_excluded_paths'] = [
      '#type' => 'details',
      '#title' => $this->t('Iframe excluded paths'),
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

    // Default roles of accounts created through the JSON:API registration
    // resource or the "espace privé" registration webform. Granting roles
    // requires "administer permissions".
    $form['registration'] = [
      '#type' => 'details',
      '#title' => $this->t('Registration'),
      '#group' => 'settings_tab',
      '#access' => $this->currentUser()->hasPermission('administer permissions'),
    ];
    $grantable = array_filter(Role::loadMultiple(), [
      UserRegistrationRolesSubscriber::class,
      'isGrantable',
    ]);
    $form['registration']['registration_default_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles granted on registration'),
      '#options' => array_map(fn($role) => $role->label(), $grantable),
      '#default_value' => $config->get('registration_default_roles') ?? [],
      '#description' => $this->t('Roles added to accounts created through the JSON:API registration resource (/api/user/register) and through the "espace privé" registration webform (vactory_espace_prive_register). Roles sent in the registration request are always ignored. Administrator roles cannot be selected.'),
    ];

    $form['iframe_excluded_paths']['iframe_paths'] = [
      '#type' => 'textarea',
      '#title' => t('Paths'),
      '#default_value' => $config->get('iframe_excluded_paths'),
      '#description' => t("Enter one value per line. <b>E.g</b>: /en/news, /node/5"),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_decoupled.settings')
      ->set('routes', $form_state->getValue('routes'))
      ->set('cache_excluded_types', array_filter($form_state->getValue('cache_excluded_types')))
      ->set('auth_roles_excluded', array_filter($form_state->getValue('auth_roles_excluded')))
      ->set('iframe_excluded_paths', $form_state->getValue('iframe_paths'));
    if ($form['registration']['#access']) {
      $roles = array_filter($form_state->getValue('registration_default_roles') ?? []);
      $config->set('registration_default_roles', array_values($roles));
    }
    $config->save();
    drupal_flush_all_caches();
    parent::submitForm($form, $form_state);
  }

}
