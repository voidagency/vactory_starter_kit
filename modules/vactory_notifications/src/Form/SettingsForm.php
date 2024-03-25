<?php

namespace Drupal\vactory_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;

/**
 * Provide the notification setting form.
 *
 * @package Drupal\vactory_notifications\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_notifications.settings'];
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
    return 'vactory_notifications_settings_form';
  }

  /**
   * The build form function.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_notifications.settings');
    $path_resolver = \Drupal::service('extension.path.resolver');
    $existing_roles = Role::loadMultiple();
    $existing_content_types = NodeType::loadMultiple();
    $node_types = [];

    $form = parent::buildForm($form, $form_state);
    $form['settings_tab'] = [
      '#type' => 'vertical_tabs',
    ];
    // Global setting Tab.
    $form['global_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Content settings'),
      '#description' => $this->t('Set the global notifications title and message to use for all content types (You can customize it later for each node on node edit page).'),
      '#group' => 'settings_tab',
    ];
    // Roles settings Tab.
    $form['roles_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles settings'),
      '#description' => $this->t('Select for each role associated users the content types which users should recieve notifications from. Empty choice means notifications are disabled for that role.'),
      '#group' => 'settings_tab',
    ];
    // Mail settings Tab.
    $form['mail_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Mail settings'),
      '#description' => $this->t('Select for each role associated users the content types which users should recieve mail notifications from. Empty choice means notifications are disabled for that role.'),
      '#group' => 'settings_tab',
    ];
    $form['mail_settings']['mail_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate sending mail'),
      '#description' => $this->t('check it to send notifications by mail'),
      '#default_value' => $config->get('mail_active'),
    ];
    $form['mail_settings']['mail_default_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mail default  subject'),
      '#description' => $this->t('Default Mail Subject to use. You can explore available notifications tokens by clicking "Browse available tokens" link bellow.'),
      '#default_value' => $config->get('mail_default_subject'),
      '#required' => TRUE,
    ];
    $form['mail_settings']['mail_default_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mail default message'),
      '#description' => $this->t('Default Mail message to use. You can explore available notifications tokens by clicking "Browse available tokens" link bellow.'),
      '#default_value' => $config->get('mail_default_message'),
      '#required' => TRUE,
    ];
    $form['mail_settings']['tree_token'] = get_token_tree();

    // Global settings.
    $form['global_settings']['notifications_default_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notifications default  title'),
      '#description' => $this->t('Default notifications title to use. You can explore available notifications tokens by clicking "Browse available tokens" link bellow.'),
      '#default_value' => $config->get('notifications_default_title'),
      '#required' => TRUE,
    ];
    $form['global_settings']['notifications_default_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notifications default message'),
      '#description' => $this->t('Default notifications message to use. You can explore available notifications tokens by clicking "Browse available tokens" link bellow.'),
      '#default_value' => $config->get('notifications_default_message'),
      '#required' => TRUE,
    ];
    $form['global_settings']['tree_token'] = get_token_tree();
    // Auto translation feature.
    $url = Url::fromRoute('config_translation.item.overview.vactory_notifications.notifications_settings')->toString();
    $translate_config_link_title = $this->t('Notifications settings translation page');
    $translate_config_link = '<a href="' . $url . '">' . $translate_config_link_title . '</a>';
    $form['global_settings']['auto_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Translate notifications automatically'),
      '#description' => $this->t('Uncheck it to translate notifications manually. Notifications default title and message are translatable under') . ' ' . $translate_config_link,
      '#default_value' => $config->get('auto_translation'),
    ];
    $form['global_settings']['notifications_lifetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notifications lifetime'),
      '#description' => $this->t('Set days number from the notification created date after which this notification is deleted in the next cron call. By default 6 days.'),
      '#default_value' => $config->get('notifications_lifetime'),
      '#required' => TRUE,
    ];
    $form['global_settings']['enable_toast'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable notifications toast'),
      '#description' => $this->t('When checked notifications bootstrap toast will be enabled for real time generated notification.'),
      '#default_value' => $config->get('enable_toast'),
    ];
    $form['global_settings']['toast_template'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Toast template'),
      '#description' => $this->t('Enter Toast template twig file path here, Default to @path/templates/notifications-toast.html.twig', ['@path' => $path_resolver->getPath('module', 'vactory_notifications')]),
      '#default_value' => $config->get('toast_template') ? $config->get('toast_template') : $path_resolver->getPath('module', 'vactory_notifications') . '/templates/notifications-toast.html.twig',
      '#states' => [
        'visible' => [
          'input[name="enable_toast"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Mail content types Tab.
    $form['mail_content_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Mail content types'),
      '#description' => $this->t('Mail message to use. You can explore available notifications tokens by clicking "Browse available tokens" link bellow.'),
      '#group' => 'settings_tab',
      '#tree' => TRUE,
    ];
    foreach ($existing_content_types as $node_type_machine_name => $content_type) {
      $form['mail_content_types'][$node_type_machine_name] = [
        '#type' => 'details',
        '#title' => $content_type->label(),
        '#group' => 'content_type_tab',
      ];
      $form['mail_content_types'][$node_type_machine_name]['mail_subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Mail subject'),
        '#description' => $this->t('Mail Subject to use. You can explore available notifications tokens by clicking "Browse available tokens" link bellow.'),
        '#default_value' => $config->get('mail_content_types')[$node_type_machine_name]['mail_subject'] ?? '',
      ];
      $form['mail_content_types'][$node_type_machine_name]['mail_message'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Mail default message'),
        '#description' => $this->t('Mail message to use. You can explore available notifications tokens by clicking "Browse available tokens" link bellow.'),
        '#format' => 'full_html',
        '#default_value' => $config->get('mail_content_types')[$node_type_machine_name]['mail_message']['value'] ?? '',
      ];
      $form['mail_content_types'][$node_type_machine_name]['tree_token'] = get_token_tree();
    }

    // Roles settings.
    foreach ($existing_roles as $key => $role) {
      $form['roles_settings'][$key] = [
        '#type' => 'details',
        '#title' => $role->label(),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      foreach ($existing_content_types as $node_type_machine_name => $content_type) {
        $node_types[$node_type_machine_name] = $content_type->label();
      }
      $form['roles_settings'][$key][$key . '_content_types'] = [
        '#type' => 'select',
        '#title' => $this->t('Existing content types'),
        '#options' => $node_types,
        '#multiple' => TRUE,
        '#default_value' => !empty($config->get($key . '_content_types')) ? $config->get($key . '_content_types') : [],
      ];

      // Mail Roles settings.
      if ($role->id() <> 'anonymous') {
        $form['mail_settings'][$key] = [
          '#type' => 'details',
          '#title' => $role->label(),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
          '#states' => [
            'visible' => [
              ':input[name="mail_active"]' => ['checked' => TRUE],
            ],
          ],
        ];

        $form['mail_settings'][$key][$key . '_content_types_mail'] = [
          '#type' => 'select',
          '#title' => $this->t('Existing content types'),
          '#options' => $node_types,
          '#multiple' => TRUE,
          '#default_value' => !empty($config->get($key . '_content_types_mail')) ? $config->get($key . '_content_types_mail') : [],
        ];

      }
      $node_types = [];
    }

    return $form;
  }

  /**
   * The submit form function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_notifications.settings');
    $existing_roles = Role::loadMultiple();
    $config->set('notifications_default_title', $form_state->getValue('notifications_default_title'))
      ->set('notifications_default_message', $form_state->getValue('notifications_default_message'))
      ->set('mail_default_subject', $form_state->getValue('mail_default_subject'))
      ->set('mail_default_message', $form_state->getValue('mail_default_message'))
      ->set('auto_translation', $form_state->getValue('auto_translation'))
      ->set('mail_active', $form_state->getValue('mail_active'))
      ->set('notifications_lifetime', $form_state->getValue('notifications_lifetime'))
      ->set('enable_toast', $form_state->getValue('enable_toast'))
      ->set('toast_template', $form_state->getValue('toast_template'))
      ->set('mail_content_types', $form_state->getValue('mail_content_types'))
      ->save();

    foreach ($existing_roles as $key => $role) {
      $config->set($key . '_content_types', array_keys($form_state->getValue($key . '_content_types')));
      if ($role->id() <> 'anonymous') {
        $config->set($key . '_content_types_mail', array_keys($form_state->getValue($key . '_content_types_mail')));
      }
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
