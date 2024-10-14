<?php

namespace Drupal\vactory_help_center\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Vactory help center search form element.
 *
 * @FormElement("vactory_help_center_search")
 */
class HelpCenterSearchElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_called_class();
    return [
      '#process' => [
        [$class, 'processElement'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the Vactory Help Center form element.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['index'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'] ?? '',
      '#description' => t('Help center search element'),
    ];
    return $element;
  }

  /**
   * Validates the Vactory Help Center form element.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
  }

}
