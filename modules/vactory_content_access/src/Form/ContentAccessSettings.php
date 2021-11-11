<?php

namespace Drupal\vactory_content_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Content access settings class.
 */
class ContentAccessSettings extends ConfigFormBase {

  /**
   * Get editable config names function.
   */
  protected function getEditableConfigNames() {
    return ['vactory_content_access.settings'];
  }

  /**
   * Get form id function.
   */
  public function getFormId() {
    return 'vactory_content_access_settings';
  }

  /**
   * Build form function.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_content_access.settings');
    $content_types = NodeType::loadMultiple();
    $error_codes = [
      404 => 'Not Found 404',
      403 => 'Forbidden 403',
    ];
    foreach ($content_types as $key => $content_type) {
      $form['content_type_detail'][$key] = [
        '#type' => 'details',
        '#title' => $content_type->label(),
        '#group' => 'tabs',
      ];
      $form['content_type_detail'][$key][$key . '_content_type'] = [
        '#type' => 'checkbox',
        '#title' => t("Activer la gestion d'accès de contenu pour le type de contenu :") . $content_type->label() . '.',
        '#description' => t("Permettre de gérer l'accès à la page détails."),
        '#default_value' => !empty($config->get($key . '_content_type')) ? $config->get($key . '_content_type') : 0,
      ];

      $form['content_type_detail'][$key][$key . '_listing_access'] = [
        '#type' => 'checkbox',
        '#title' => t("Activer la gestion d'accès de contenu aux page détails et listing."),
        '#default_value' => !empty($config->get($key . '_listing_access')) ? $config->get($key . '_listing_access') : 0,
      ];
      $form['content_type_detail'][$key][$key . '_redirect_to'] = [
        '#type' => 'select',
        '#title' => t('Redirect to'),
        '#options' => $error_codes,
        '#default_value' => !empty($config->get($key . '_redirect_to')) ? $config->get($key . '_redirect_to') : $error_codes[404],
      ];

    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit Form function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_content_access.settings');
    $existing_node_types = NodeType::loadMultiple();
    $nids_rebuild = [];
    foreach ($existing_node_types as $key => $value) {
      $access_content_type = $form_state->getValue($key . '_content_type');
      $config->set($key . '_content_type', $access_content_type);
      $config->set($key . '_listing_access', $form_state->getValue($key . '_listing_access'));
      $config->set($key . '_redirect_to', $form_state->getValue($key . '_redirect_to'));
      if ($access_content_type == 1) {
        $nids = \Drupal::entityQuery('node')
          ->condition('type', $key)
          ->execute();
        $nids = isset($nids) && !empty($nids) ? array_values($nids)[0] : [];
        array_push($nids_rebuild, $nids);
      }
    }
    $config->save();
    $operations = [
      ['rebuild_nodes_access', [$nids_rebuild]],
    ];
    $batch = [
      'title' => $this->t('Rebuild access All Nodes ...'),
      'operations' => $operations,
      'finished' => 'rebuild_nodes_access_finished',
    ];
    batch_set($batch);
    parent::submitForm($form, $form_state);
  }

}
