<?php

namespace Drupal\vactory_facebook_pixel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/**
 * Facebook Pixel Settings Form.
 *
 * @package Drupal\vactory_facebook_pixel\Form
 */
class FacebookPixelSettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_facebook_pixel.settings'];
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
    return 'vactory_facebook_pixel_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_facebook_pixel.settings');

    $form['facebook_pixel_general'] = [
      '#type' => 'fieldset',
      '#title' => t("General"),
    ];
    $form['facebook_pixel_path'] = [
      '#type' => 'details',
      '#title' => t("Request path"),
      '#description' => t('On this and the following tabs, specify the concerned paths .'),
      '#collapsed' => TRUE,
    ];
    $form['facebook_pixel_roles'] = [
      '#type' => 'details',
      '#title' => t("Rôle de l'utilisateur"),
      '#collapsed' => TRUE,
    ];
    $form['facebook_pixel_content'] = [
      '#type' => 'details',
      '#title' => t("Content type"),
      '#collapsed' => TRUE,
    ];
    $form['facebook_pixel_language'] = [
      '#type' => 'details',
      '#title' => t("Language"),
      '#collapsed' => TRUE,
    ];
    $form['facebook_pixel_route'] = [
      '#type' => 'details',
      '#title' => t("Route name"),
      '#description' => t("On this and the following tabs, specify route names that concern 'Registration' event."),
      '#collapsed' => TRUE,
    ];

    $form['facebook_pixel_general']['fv_endpoint'] = [
      '#type' => 'textfield',
      '#title' => t("Facebook Validation Endpoint"),
      '#default_value' => !empty($config->get('fv_endpoint')) ? $config->get('fv_endpoint') : '',
    ];
    $form['facebook_pixel_general']['pixel_id'] = [
      '#type' => 'textfield',
      '#title' => t("Facebook Pixel ID"),
      '#default_value' => !empty($config->get('pixel_id')) ? $config->get('pixel_id') : '',
    ];
    $form['facebook_pixel_general']['fb_key'] = [
      '#type' => 'textarea',
      '#title' => t("Facebook Key"),
      '#default_value' => !empty($config->get('fb_key')) ? $config->get('fb_key') : '',
    ];

    $form['facebook_pixel_path']['manage_listed_paths'] = [
      '#type' => 'radios',
      '#title' => t("Track view content event on the following paths"),
      '#options' => [
        'exclude_listed_paths' => 'All paths except the listed paths',
        'include_listed_paths' => 'Only the listed paths',
      ],
      '#default_value' => !empty($config->get('manage_listed_paths')) ? $config->get('manage_listed_paths') : 'exclude_listed_paths',
    ];
    $args = [
      '%node' => '/node',
      '%user-wildcard' => '/user/*',
      '%front' => '<front>',
    ];
    $form['facebook_pixel_path']['listed_paths'] = [
      '#type' => 'textarea',
      '#title' => t("Listed paths"),
      '#default_value' => !empty($config->get('listed_paths')) ? $config->get('listed_paths') : '',
      '#description' => t('Enter one relative path per line using the "*" character as a wildcard. Example paths are: "%node" for the node page, "%user-wildcard" for each individual user, and "%front" for the front page.', $args),
    ];

    $form['facebook_pixel_roles']['manage_listed_roles'] = [
      '#type' => 'radios',
      '#title' => t("Track view content event for the following roles"),
      '#options' => [
        'exclude_listed_roles' => 'All paths except the listed paths',
        'include_listed_roles' => 'Only the listed paths',
      ],
      '#default_value' => !empty($config->get('manage_listed_roles')) ? $config->get('manage_listed_roles') : 'exclude_listed_roles',
    ];
    $roles = Role::loadMultiple();
    $options_roles = [];
    foreach ($roles as $key => $role) {
      $options_roles[$key] = $role->label();
    }
    $form['facebook_pixel_roles']['listed_roles'] = [
      '#type' => 'checkboxes',
      '#title' => t("Listed roles"),
      '#default_value' => !empty($config->get('listed_roles')) ? $config->get('listed_roles') : [],
      '#options' => $options_roles,
    ];

    $form['facebook_pixel_content']['manage_listed_content'] = [
      '#type' => 'radios',
      '#title' => t("Track view content event for the following content types"),
      '#options' => [
        'exclude_listed_content' => 'All paths except the listed paths',
        'include_listed_content' => 'Only the listed paths',
      ],
      '#default_value' => !empty($config->get('manage_listed_content')) ? $config->get('manage_listed_content') : 'exclude_listed_content',
    ];
    $content_types = NodeType::loadMultiple();
    $options_types = [];
    foreach ($content_types as $key => $content_type) {
      $options_types[$key] = $content_type->label();
    }
    $form['facebook_pixel_content']['listed_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => t("Listed content types"),
      '#default_value' => !empty($config->get('listed_content_types')) ? $config->get('listed_content_types') : [],
      '#options' => $options_types,
    ];

    $form['facebook_pixel_language']['manage_listed_language'] = [
      '#type' => 'radios',
      '#title' => t("Track view content event for the following languages"),
      '#options' => [
        'exclude_listed_languages' => 'All paths except the listed languages',
        'include_listed_languages' => 'Only the listed languages',
      ],
      '#default_value' => !empty($config->get('manage_listed_language')) ? $config->get('manage_listed_language') : 'exclude_listed_languages',
    ];
    $languages = \Drupal::languageManager()->getLanguages();
    $options_language = [];
    foreach ($languages as $key => $language) {
      $options_language[$key] = $language->getName();
    }
    $form['facebook_pixel_language']['listed_languages'] = [
      '#type' => 'checkboxes',
      '#title' => t("Listed languages"),
      '#default_value' => !empty($config->get('listed_languages')) ? $config->get('listed_languages') : [],
      '#options' => $options_language,
    ];

    $form['facebook_pixel_route']['listed_routes'] = [
      '#type' => 'textarea',
      '#title' => t("Listed route names that concern 'Registration' event"),
      '#default_value' => !empty($config->get('listed_routes')) ? $config->get('listed_routes') : '',
      '#description' => t("Enter one relative route name per line. Example route name: 'vactory_api.fb_add_user'. 'user.register' route already included"),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_facebook_pixel.settings');
    $config->set('fv_endpoint', $form_state->getValue('fv_endpoint'))
      ->set('pixel_id', $form_state->getValue('pixel_id'))
      ->set('fb_key', $form_state->getValue('fb_key'))
      ->set('manage_listed_paths', $form_state->getValue('manage_listed_paths'))
      ->set('listed_paths', $form_state->getValue('listed_paths'))
      ->set('manage_listed_roles', $form_state->getValue('manage_listed_roles'))
      ->set('listed_roles', $form_state->getValue('listed_roles'))
      ->set('manage_listed_content', $form_state->getValue('manage_listed_content'))
      ->set('listed_content_types', $form_state->getValue('listed_content_types'))
      ->set('manage_listed_language', $form_state->getValue('manage_listed_language'))
      ->set('listed_languages', $form_state->getValue('listed_languages'))
      ->set('listed_routes', $form_state->getValue('listed_routes'))
      ->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
