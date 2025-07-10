<?php

namespace Drupal\vactory_announcements\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Defines a form that configures forms module settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_announcements_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_announcements.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_announcements.settings');
    $form = parent::buildForm($form, $form_state);
    $form['settings_tab'] = [
      '#type' => 'vertical_tabs',
    ];
    // Email Add advert admin Tab.
    $form['notification_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification Add advert admin'),
      '#description' => $this->t('Set the Announcement pending approval email subject and message to be Sent to webmaster or admin.'),
      '#group' => 'settings_tab',
    ];
    // Validation Tab.
    $form['validation_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Posting validation of the ad'),
      '#description' => $this->t('Set the validation email subject and message to be Sent to announcer.'),
      '#group' => 'settings_tab',
    ];
    // Email Add advert admin Tab.
    // Get admins && webmasters.
    $concerned_roles = ['administrator', 'webmaster'];
    $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
    $query->condition('roles', $concerned_roles, 'IN');
    $query->accessCheck(FALSE);
    $uids = $query->execute();
    $users = User::loadMultiple($uids);
    // Build the receivers array.
    $receivers = [];
    foreach ($users as $user) {
      $receivers[$user->id()] = $user->getDisplayName();
    }
    $form['notification_settings']['notification_mail_receiver'] = [
      '#type' => 'select',
      '#title' => $this->t('Email To'),
      '#multiple' => TRUE,
      '#options' => $receivers,
      '#default_value' => !empty($config->get('notification_mail_receiver')) ? $config->get('notification_mail_receiver') : '',
    ];
    $form['notification_settings']['notification_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('notification_title'),
    ];
    $form['notification_settings']['notification_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email message'),
      '#format' => 'full_html',
      '#default_value' => $config->get('notification_message'),
    ];
    $form['notification_settings']['notification_parameters'] = [
      '#type' => 'markup',
      '#markup' => '<div><h5>Parameters to use :</h5>
        <p>
        !link_annonce<br/>
        !link_moderate<br/>
        !site_name<br/>
        !name<br/>
        !period_validity<br/>
        !date_end<br/>
        !date_start<br/>
        !title<br/>
        !body<br/>
        !country<br/>
        !site<br/>
        !facebook<br/>
        !twitter<br/>
        !site<br/>
        !phone<br/>
        !mail
        </p>
      </div>',
    ];
    // Validation  Tab.
    $form['validation_settings']['v_notification_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('v_notification_title'),
    ];
    $form['validation_settings']['v_notification_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email message'),
      '#format' => 'full_html',
      '#default_value' => $config->get('v_notification_message'),
    ];
    $form['validation_settings']['v_notification_parameters'] = [
      '#type' => 'markup',
      '#markup' => '<div><h5>Parameters to use :</h5>
        <p>
        !link_annonce<br/>
        !link_delete<br/>
        !site_name<br/>
        !name<br/>
        !period_validity<br/>
        !date_end<br/>
        !date_start<br/>
        !title<br/>
        !body<br/>
        !country<br/>
        !site<br/>
        !facebook<br/>
        !twitter<br/>
        !site<br/>
        !phone<br/>
        !mail
        </p>
      </div>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_announcements.settings');
    $config->set('notification_mail_receiver', $form_state->getValue('notification_mail_receiver'))
      ->set('notification_title', $form_state->getValue('notification_title'))
      ->set('notification_message', $form_state->getValue('notification_message')['value'])
      ->set('v_notification_title', $form_state->getValue('v_notification_title'))
      ->set('v_notification_message', $form_state->getValue('v_notification_message')['value'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
