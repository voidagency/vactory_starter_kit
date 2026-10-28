<?php

namespace Drupal\vactory_keycloak\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Drupal\social_auth\Plugin\Network\NetworkInterface;

/**
 * Settings form for Social Auth Keycloak.
 */
class KeycloakAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'vactory_keycloak_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return array_merge(
      parent::getEditableConfigNames(),
      ['vactory_keycloak.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NetworkInterface $network = NULL): array {
    /** @var \Drupal\social_auth\Plugin\Network\NetworkInterface $network */
    $network = $this->networkManager->createInstance('vactory_keycloak');
    $form = parent::buildForm($form, $form_state, $network);

    $config = $this->config('vactory_keycloak.settings');

    $form['network']['#description'] = $this->t('You need to first create an Application client on your Keycloak server.');

    $form['network']['app_server_url'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Server URL'),
      '#default_value' => $config->get('app_server_url'),
      '#description' => $this->t('Example https://localhost:8443/auth'),
      '#weight' => -10,
    ];

    $form['network']['app_realm'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application Realm'),
      '#default_value' => $config->get('app_realm'),
      '#weight' => -9,
    ];

    // Public Keycloak clients have no secret.
    $form['network']['client_secret']['#required'] = FALSE;
    $form['network']['authorized_redirect_url']['#description'] = $this->t('Copy this value to <em>Valid OAuth redirect URIs</em> field of your Keycloak App settings.');

    $form['network']['advanced']['no_verify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No cert verification'),
      '#default_value' => $config->get('no_verify'),
    ];

    $form['network']['advanced']['scopes']['#description'] = $this->t("Define any additional scopes to be requested, separated by a comma.<br>The scope 'profile' is added by default and always requested.");

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->config('vactory_keycloak.settings')
      ->set('app_server_url', $values['app_server_url'])
      ->set('app_realm', $values['app_realm'])
      ->set('no_verify', $values['no_verify'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
