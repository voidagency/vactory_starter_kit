<?php

namespace Drupal\vactory_api_key_auth\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Class ApiKeyForm.
 *
 * @package Drupal\api_key_auth\Form
 */
class ApiKeyForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $api_key = $this->entity;
    $hex = isset($api_key->key) ? $api_key->key : substr(hash('sha256', random_bytes(16)), 0, 32);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine Name'),
      '#maxlength' => 255,
      '#default_value' => $api_key->label(),
      '#description' => $this->t("Machine Name for the API Key."),
      '#required' => TRUE,
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#maxlength' => 42,
      '#default_value' => $hex,
      '#description' => $this->t("The generated API Key for an user."),
      '#required' => TRUE,
    ];

    $form['user_uuid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('User'),
      '#target_type' => 'user',
      '#validate_reference' => FALSE,
      '#maxlength' => 60,
      '#description' => $this->t("Please select the user who gets authenticated with that API Key."),
      '#default_value' => (!empty($api_key->user_uuid)) ? User::load($api_key->user_uuid) : NULL
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $api_key->id(),
      '#machine_name' => [
        'exists' => '\Drupal\vactory_api_key_auth\Entity\ApiKey::load',
      ],
      '#disabled' => !$api_key->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $api_key = $this->entity;
    $status = $api_key->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label API Key.', [
          '%label' => $api_key->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label API Key.', [
          '%label' => $api_key->label(),
        ]));
    }

    $form_state->setRedirectUrl($api_key->toUrl('collection'));
  }

  /**
   * Helper function to get user entity options for select widget.
   *
   * @parameter String $machine_name
   *   user name
   *
   * @return array
   *   Select options for form
   */
  public function getUser() {
    $options = [];

    $options_source = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();

    foreach ($options_source as $item) {
      $key = $item->uuid->value;
      $value = $item->name->value;
      $options[$key] = $value;
    }
    return $options;
  }

}
