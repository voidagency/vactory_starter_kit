<?php

namespace Drupal\vactory_decoupled_breadcrumb\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Configure Decoupled breadcrumb settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_decoupled_breadcrumb_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_decoupled_breadcrumb.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_decoupled_breadcrumb.settings');
    $form['show_home'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show home page link'),
      '#description' => $this->t('Add home page link as first element of breadcrumb links list'),
      '#default_value' => $config->get('show_home'),
    ];
    $form['home_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Home page link title'),
      '#description' => $this->t('Home page link title, default to "Home"'),
      '#default_value' => $config->get('home_title'),
      '#states' => [
        'visible' => [
          'input[name="show_home"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['show_current_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show current page title'),
      '#default_value' => $config->get('show_current_page'),
    ];
    $form['show_current_langcode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show current language code'),
      '#description' => $this->t('Add current langcode as first element of breadcrumb links list'),
      '#default_value' => $config->get('show_current_langcode'),
    ];
    $menus = \Drupal::entityTypeManager()->getStorage('menu')
      ->loadMultiple();
    $menus = array_map(function ($menu) {
      return $menu->label();
    }, $menus);
    $form['enabled_menu'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled menus'),
      '#options' => $menus,
      '#description' => $this->t('The menu where the current page match has taken place.'),
      '#default_value' => $config->get('enabled_menu') ?? ['main'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vactory_decoupled_breadcrumb.settings')
      ->set('show_home', $form_state->getValue('show_home'))
      ->set('home_title', $form_state->getValue('home_title'))
      ->set('show_current_page', $form_state->getValue('show_current_page'))
      ->set('show_current_langcode', $form_state->getValue('show_current_langcode'))
      ->set('enabled_menu', array_filter($form_state->getValue('enabled_menu')))
      ->save();
    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
