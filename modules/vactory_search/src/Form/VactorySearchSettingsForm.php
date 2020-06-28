<?php

namespace Drupal\vactory_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide a config form for vactory search.
 *
 * @package Drupal\vactory_search\Form
 */
class VactorySearchSettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_search.settings'];
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
    return 'vactory_search_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_search.settings');
    $form['display_settings_group'] = [
      '#type' => 'details',
      '#title' => t('Affichage'),
    ];

    $form['display_settings_group']['use_node_summary'] = [
      '#type' => 'checkbox',
      '#title' => t('Afficher uniquement le contenu du champs description dans le resultat de recherche'),
      '#default_value' => $config->get('use_node_summary'),
      '#description' => t("Si la case ci-dessus est décochée alors le résultat contiendra un extrait de contenu du noeud avec mot clé en gras."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_search.settings')
      ->set('use_node_summary', $form_state->getValue('use_node_summary'))
      ->save();

    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
