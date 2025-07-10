<?php

namespace Drupal\vactory_seo\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide a form element vactory_seo_search.
 *
 * @FormElement("vactory_seo_search")
 */
class VactorySeoSearchElement extends FormElement {

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

    $element['keyword'] = [
      '#type'          => 'textfield',
      '#title'         => t('Keyword'),
      '#default_value' => $element['#default_value']['keyword'] ?? '',
      '#description' => t('you can enter either a specific keyword or a token – both are supported.'),
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
