<?php

namespace Drupal\vactory_login_token\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Config Form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Function Get Form Id.
   */
  public function getFormId() {
    return 'token_login_settings';
  }

  /**
   * Function build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('token_login.settings');
    if ($config->get('expiration') == '') {
      $config->set('expiration', 40000)->save();
    }
    $form['expiration_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expiration'),
      '#required' => TRUE,
      '#default_value' => $config->get('expiration_time'),
      '#description' => $this->t('Expiration des liens en secondes.'),
    ];
    $form['expiration_status'] = [
      '#type' => 'checkbox',
      '#title' => 'activation',
      '#default_value' => $config->get('expirationStatus'),
      '#description' => $this->t("ne prend pas le temp d'éxpiration en considiration"),
    ];
    $form['destination'] = [
      '#type' => 'textfield',
      '#title' => 'destination',
      '#default_value' => $config->get('destination'),
      '#description' => $this->t("Ce champ n'est pas obligatoire, distination par defaut: profile de utilisateur"),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Funtion Submit Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('token_login.settings');
    $config->set('expiration_time', $values['expiration_time'])->save();
    $config->set('expirationStatus', $values['expiration_status'])->save();
    $config->set('destination', $values['destination'])->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Function Get Editable Config Names.
   */
  public function getEditableConfigNames() {
    return [
      'token_login.settings',
    ];
  }

}
