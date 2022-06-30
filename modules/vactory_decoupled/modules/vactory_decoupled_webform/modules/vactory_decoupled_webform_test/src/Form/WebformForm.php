<?php

namespace Drupal\vactory_decoupled_webform_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Webform class.
 */
class WebformForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'dynamic_field_hello_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $d = \Drupal::service('vactory.webform.normalizer');
    $a = $d->normalize('contact');

    $form['webform'] = [
      '#type' => 'webform_decoupled',
      '#title' => t('Form.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $webform = $form_state->getValue('webform');
    $webform_id = $webform['id'];
    $d = \Drupal::service('vactory.webform.normalizer');
    $d->normalize($webform_id);
  }
}
