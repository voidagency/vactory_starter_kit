<?php

namespace Drupal\vactory_notifications_role_based\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/**
 * Configure Intranet Notifications settings for this site.
 */
class NotificationsRolesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_notifications_role_based';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_notifications_role_based.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_notifications_role_based.settings');
    $existing_roles = Role::loadMultiple();
    $existing_content_types = NodeType::loadMultiple();

    $form['settings_tab'] = [
      '#type' => 'vertical_tabs',
      '#tree' => TRUE,
    ];

    // Add new tab for "Listes de diffusion".
    $form['roles_based_notifications'] = [
      '#type' => 'details',
      '#title' => $this->t("Rôles"),
      '#group' => 'settings_tab',
      '#tree' => TRUE,
    ];

    foreach ($existing_roles as $key => $role) {
      if ($role->id() === 'anonymous' || $role->id() === 'authenticated') {
        continue;
      }
      $form['roles_based_notifications'][$key] = [
        '#type' => 'details',
        '#title' => $role->label(),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      foreach ($existing_content_types as $node_type_machine_name => $content_type) {
        $node_types[$node_type_machine_name] = $content_type->label();
      }
      $form['roles_based_notifications'][$key][$key . '_content_types'] = [
        '#type' => 'select',
        '#title' => $this->t('Existing content types'),
        '#options' => $node_types,
        '#multiple' => TRUE,
        '#default_value' => !empty($config->get($key . '_content_types')) ? $config->get($key . '_content_types') : [],
      ];

      $node_types = [];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_notifications_role_based.settings');
    $existing_roles = Role::loadMultiple();
    foreach ($existing_roles as $key => $role) {
      if ($role->id() <> 'anonymous' && $role->id() <> 'authenticated') {
        $config->set($key . '_content_types', array_keys($form_state->getValues()["roles_based_notifications"][$key][$key . '_content_types']));
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
