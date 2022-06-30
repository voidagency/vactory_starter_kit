<?php

namespace Drupal\vactory_decoupled_tests\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class JsonApiCollectionFormTester extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'json_api_collection_form_tester';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['json'] = [
      '#type' => 'json_api_collection',
      '#title' => $this->t('Your phone number'),
      '#default_value' => [
        'resource' => 'node--vactory_news',
        'filters' => 'fields[node--vactory_news]=drupal_internal__nid,title,field_vactory_news_theme,field_vactory_media' . "\n" . 
        'fields[taxonomy_term--vactory_news_theme]=tid,name' . "\n" . 
        'fields[media--image]=name,thumbnail' . "\n" . 
        'fields[file--image]=filename,uri'. "\n" . 
        'include=field_vactory_news_theme,field_vactory_media,field_vactory_media.thumbnail' . "\n" .
        'filter[category][condition][path]=field_vactory_news_theme.drupal_internal__tid' . "\n" .
        'filter[category][condition][operator]=%3D' . "\n" .
        'filter[category][condition][value]=3',
      ]
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // if (strlen($form_state->getValue('phone_number')) < 3) {
    //   $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
    // }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('json');
    $svc = \Drupal::service("vactory_decoupled.jsonapi.generator");
    $result = $svc->fetch($value);
    dpm($value);
    dpm($result);

    $this->messenger()->addStatus($this->t('OK'));
  }

}