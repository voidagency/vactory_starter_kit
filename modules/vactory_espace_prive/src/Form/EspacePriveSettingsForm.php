<?php

namespace Drupal\vactory_espace_prive\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Espace Prive Settings Form.
 *
 * @package Drupal\vactory_espace_prive\Form
 */
class EspacePriveSettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_espace_prive.settings'];
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
    return 'vactory_espace_prive_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_espace_prive.settings');

    $form['password_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t("Webmaster's password lifetime (in days)"),
      '#min' => 0,
      '#description' => $this->t('Set the webmaster users password lifetime in days (by default 15 days), to disable password expiration set lifetime to 0'),
      '#default_value' => !empty($config->get('password_lifetime')) ? $config->get('password_lifetime') : 15,
    ];
    $form['espace_prive_paths'] = [
      '#type' => 'fieldset',
      '#title' => t("Chemins Espace Privée"),
    ];
    $form['espace_prive_metatag'] = [
      '#type' => 'details',
      '#title' => t("Espace privé Metatag"),
      '#collapsed' => TRUE,
    ];
    $form['espace_prive_password_suggestion'] = [
      '#type' => 'details',
      '#title' => t("Suggestion de mot de passe"),
      '#collapsed' => TRUE,
    ];
    $form['espace_prive_metatag']['register_page'] = [
      '#type' => 'fieldset',
      '#title' => t("Page de création d'un compte"),
    ];
    $form['espace_prive_metatag']['login_page'] = [
      '#type' => 'fieldset',
      '#title' => t("Page d'authentification"),
    ];

    $form['espace_prive_paths']['path_login'] = [
      '#type' => 'textfield',
      '#title' => t("Chemin d'authentification"),
      '#default_value' => !empty($config->get('path_login')) ? $config->get('path_login') : '/espace-prive/login',
      '#description' => "Par défaut le chemin utilisé pour l'authentification est <strong>/espace-prive/login</strong>",
    ];
    $form['espace_prive_paths']['path_register'] = [
      '#type' => 'textfield',
      '#title' => t("Chemin de création de compte"),
      '#default_value' => !empty($config->get('path_register')) ? $config->get('path_register') : '/espace-prive/register',
      '#description' => "Par défaut le chemin utilisé pour la création de compte est <strong>/espace-prive/register</strong>",
    ];
    $form['espace_prive_paths']['path_profile'] = [
      '#type' => 'textfield',
      '#title' => t("Chemin de gestion de profil"),
      '#default_value' => !empty($config->get('path_profile')) ? $config->get('path_profile') : '/espace-prive/profile',
      '#description' => "Par défaut le chemin utilisé pour la gestion de profil est <strong>/espace-prive/profile</strong>",
    ];
    $form['espace_prive_paths']['path_password'] = [
      '#type' => 'textfield',
      '#title' => t("Chemin de réinitialisation de mot de passe"),
      '#default_value' => !empty($config->get('path_password')) ? $config->get('path_password') : '/espace-prive/password',
      '#description' => "Par défaut le chemin utilisé pour la réinitialisation de mot de passe est <strong>/espace-prive/password</strong>",
    ];
    $form['espace_prive_paths']['path_welcome'] = [
      '#type' => 'textfield',
      '#title' => t("Chemin de la page bienvenue"),
      '#default_value' => !empty($config->get('path_welcome')) ? $config->get('path_welcome') : '/espace-prive/welcome',
      '#description' => "Par défaut le chemin utilisé pour la page bienvenue est <strong>/espace-prive/welcome</strong>",
    ];

    $form['espace_prive_metatag']['register_page']['metatag_register_title'] = [
      '#type' => 'textfield',
      '#title' => t('Titre de la page'),
      '#default_value' => $config->get('metatag_register_title'),
    ];
    $form['espace_prive_metatag']['register_page']['metatag_register_description'] = [
      '#type' => 'textarea',
      '#title' => t('Description de la page'),
      '#default_value' => $config->get('metatag_register_description'),
    ];
    $form['espace_prive_metatag']['login_page']['metatag_login_title'] = [
      '#type' => 'textfield',
      '#title' => t('Titre de la page'),
      '#default_value' => $config->get('metatag_login_title'),
    ];
    $form['espace_prive_metatag']['login_page']['metatag_login_description'] = [
      '#type' => 'textarea',
      '#title' => t('Description de la page'),
      '#default_value' => $config->get('metatag_login_description'),
    ];
    $form['espace_prive_password_suggestion']['enable_password_suggestion'] = [
      '#type' => 'checkbox',
      '#title' => t("Activer la suggestion des mots de passe"),
      '#default_value' => !empty($config->get('enable_password_suggestion')) ? $config->get('enable_password_suggestion') : FALSE,
    ];
    $form['domain_black_list'] = [
      '#type' => 'textarea',
      '#default_value' => !empty($config->get('domain_black_list')) ? $config->get('domain_black_list') : '',
      '#description' => $this->t('Enter black listed domains separated by ";" character. Ex: hotmail.com;yopmail.com'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_espace_prive.settings');
    $domains = $form_state->getValue('domain_black_list');
    $domains = !empty($domains) ? str_replace(' ', '', $domains) : '';
    $config->set('path_login', $form_state->getValue('path_login'))
      ->set('path_login', $form_state->getValue('path_login'))
      ->set('path_register', $form_state->getValue('path_register'))
      ->set('path_profile', $form_state->getValue('path_profile'))
      ->set('path_password', $form_state->getValue('path_password'))
      ->set('path_welcome', $form_state->getValue('path_welcome'))
      ->set('metatag_register_title', $form_state->getValue('metatag_register_title'))
      ->set('metatag_register_description', $form_state->getValue('metatag_register_description'))
      ->set('metatag_login_title', $form_state->getValue('metatag_login_title'))
      ->set('metatag_login_description', $form_state->getValue('metatag_login_description'))
      ->set('password_lifetime', $form_state->getValue('password_lifetime'))
      ->set('enable_password_suggestion', $form_state->getValue('enable_password_suggestion'))
      ->set('domain_black_list', $domains)
      ->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
