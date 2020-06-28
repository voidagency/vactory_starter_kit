<?php

namespace Drupal\vactory_espace_prive\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EspacePriveSettingsForm.
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

    $form['espace_prive_paths'] = [
      '#type' => 'fieldset',
      '#title' => t("Chemins Espace Privée"),
    ];
    $form['espace_prive_redirect'] = [
      '#type' => 'fieldset',
      '#title' => t("Redirections de chemins natifs de Drupal"),
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

    $form['espace_prive_redirect']['redirect_mode'] = [
      '#type' => 'radios',
      '#options' => [
        'to_not_found' => t('Redirection 404 (Page non trouvée)'),
        'to_new_path' => t('Redirection vers les nouveaux chemins du module espace privée'),
      ],
      '#default_value' => $config->get('redirect_mode'),
      '#description' => 'Vous pouvez choisir le mode de redirection qui vous convient pour les chemins natifs de Drupal: <strong>/user/login, /user/register /user/UID/edit /user /user/password</strong>.',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_espace_prive.settings');
    $config->set('path_login', $form_state->getValue('path_login'))
      ->set('path_login', $form_state->getValue('path_login'))
      ->set('path_register', $form_state->getValue('path_register'))
      ->set('path_profile', $form_state->getValue('path_profile'))
      ->set('path_password', $form_state->getValue('path_password'))
      ->set('path_welcome', $form_state->getValue('path_welcome'))
      ->set('redirect_mode', $form_state->getValue('redirect_mode'))
      ->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
