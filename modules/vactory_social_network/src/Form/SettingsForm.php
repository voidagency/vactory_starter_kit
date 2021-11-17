<?php

namespace Drupal\vactory_social_network\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class provide a simple config to disable and enable (add post, add comments).
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Get Editable Config Names Function.
   */
  protected function getEditableConfigNames() {
    return ['vactory_social_network.settings'];
  }

  /**
   * Get Form Id fuction.
   */
  public function getFormId() {
    return 'vactory_social_network_settings';
  }

  /**
   * Build form function.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_social_network.settings');
    $form['container'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Network Settings'),
    ];

    $form['container']['enable_add_post'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Activer l'ajout du post."),
      '#default_value' => $config->get('enable_post'),
    ];

    $form['container']['enable_comments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer les commentaires.'),
      '#default_value' => $config->get('enable_comments'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit form function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enable_post = $form_state->getValue('enable_add_post');
    $enable_comments = $form_state->getValue('enable_comments');
    $this->config('vactory_social_network.settings')
      ->set('enable_post', $enable_post)
      ->set('enable_comments', $enable_comments)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
