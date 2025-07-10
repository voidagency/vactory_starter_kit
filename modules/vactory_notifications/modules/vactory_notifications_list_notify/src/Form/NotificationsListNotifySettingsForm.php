<?php

namespace Drupal\vactory_notifications_list_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Intranet Notifications settings for this site.
 */
class NotificationsListNotifySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_notifications_list_notify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_notifications_list_notify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_notifications_list_notify.settings');
    $form['settings_tab'] = [
      '#type' => 'vertical_tabs',
      '#tree' => TRUE,
    ];

    // Add new tab for "Listes de diffusion".
    $form['listes_de_diffusion'] = [
      '#type' => 'details',
      '#title' => $this->t("Listes de diffusion"),
      '#group' => 'settings_tab',
      '#tree' => TRUE,
    ];

    $form['listes_de_diffusion']['active_list_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Activer l'envoi par la liste des diffusion"),
      '#default_value' => $config->get('active_list_notify') ?? FALSE,
    ];

    $form['listes_de_diffusion']['email_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];

    $form['listes_de_diffusion']['email_container']['email_input'] = [
      '#type' => 'email',
      '#title' => $this->t('Ajouter à la liste de diffusion'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Entrer ici la liste diffusion'),
    ];

    $form['listes_de_diffusion']['email_container']['add_email'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ajouter la liste de diffusion'),
      '#submit' => ['::addEmailToList'],
      '#ajax' => [
        'callback' => '::updateEmailList',
        'wrapper' => 'email-list-wrapper',
      ],
    ];

    $form['listes_de_diffusion']['email_list'] = [
      '#type' => 'table',
      '#header' => [$this->t('Email'), $this->t('Actions')],
      '#empty' => $this->t('No emails in the list.'),
      '#prefix' => '<div id="email-list-wrapper">',
      '#suffix' => '</div>',
    ];

    $email_list = $config->get('listes_de_diffusion.email_list') ?? [];
    foreach ($email_list as $key => $email) {
      $form['listes_de_diffusion']['email_list'][$key]['email'] = [
        '#markup' => $email,
      ];
      $form['listes_de_diffusion']['email_list'][$key]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_' . $key,
        '#submit' => ['::removeEmailFromList'],
        '#ajax' => [
          'callback' => '::updateEmailList',
          'wrapper' => 'email-list-wrapper',
        ],
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('vactory_notifications_list_notify.settings');
    $config->set('active_list_notify', $form_state->getValue([
      'listes_de_diffusion',
      'active_list_notify',
    ]))->save();
  }

  /**
   * Add email to list.
   */
  public function addEmailToList(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue([
      'listes_de_diffusion',
      'email_container',
      'email_input',
    ]);
    $config = $this->config('vactory_notifications_list_notify.settings');
    $email_list = $config->get('listes_de_diffusion.email_list') ?? [];

    if (!in_array($email, $email_list) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $email_list[] = $email;
      $config->set('listes_de_diffusion.email_list', $email_list)->save();
      $form_state->setRebuild();
    }
  }

  /**
   * Remove email from list.
   */
  public function removeEmailFromList(array &$form, FormStateInterface $form_state) {
    $triggered_element = $form_state->getTriggeringElement();
    $key_to_remove = str_replace('remove_', '', $triggered_element['#name']);

    $config = $this->config('vactory_notifications_list_notify.settings');
    $email_list = $config->get('listes_de_diffusion.email_list') ?? [];

    if (isset($email_list[$key_to_remove])) {
      unset($email_list[$key_to_remove]);
      $config->set('listes_de_diffusion.email_list', array_values($email_list))
        ->save();
    }

    $form_state->setRebuild();
  }

  /**
   * Update email list.
   */
  public function updateEmailList(array &$form, FormStateInterface $form_state) {
    return $form['listes_de_diffusion']['email_list'];
  }

}
