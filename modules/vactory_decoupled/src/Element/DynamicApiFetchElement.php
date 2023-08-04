<?php

namespace Drupal\vactory_decoupled\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide a form element for retieving data from APIs.
 *
 * @FormElement("dynamic_api_fetch")
 */
class DynamicApiFetchElement extends FormElement {


  /**
   * {@inheritDoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input'            => TRUE,
      '#default_value'    => [],
      '#process'          => [
        [$class, 'processElement'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#theme_wrappers'   => ['form_element'],
    ];
  }

  /**
   * Element process callback.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;

    $element['url'] = [
      '#type'          => 'textfield',
      '#title'         => t('Request URL'),
      '#placeholder'   => 'Enter Request URL',
      '#default_value' => $element['#default_value']['url'] ?? '',
    ];

    $element['query_params'] = [
      '#type'          => 'textarea',
      '#title'         => t('Query Params'),
      '#placeholder'   => 'Enter Query Params',
      '#default_value' => $element['#default_value']['query_params'] ?? '',
      '#description'   => "The 'Query Parameters' input field allows you to specify query parameters for your request.<br>
                         Enter each query parameter on a separate line, with key and value separated by the '=' symbol.<br>
                         This format ensures accurate processing of the data when submitting the form.",
    ];

    $element['headers'] = [
      '#type'          => 'textarea',
      '#title'         => t('Headers'),
      '#placeholder'   => 'Headers',
      '#default_value' => $element['#default_value']['headers'] ?? '',
      '#description'   => "The 'Headers' input field allows you to specify headers for your request.<br>
                         Enter each header on a separate line, with key and value separated by the '=' symbol.<br>
                         This format ensures accurate processing of the data when submitting the form.",
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // Add element validation here.
  }


}
