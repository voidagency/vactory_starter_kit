<?php

namespace Drupal\vactory_decoupled\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Firebase key form.
 */
class FirebaseKeyForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_decoupled_firebase_key';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Firebase key'),
      '#default_value' => $state->get('firebase_key'),
      '#maxlength' => 1024,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    $key = !empty($form_state->getValue('key')) ? $form_state->getValue('key') : '';
    $state->set('firebase_key', $key);
  }

}
