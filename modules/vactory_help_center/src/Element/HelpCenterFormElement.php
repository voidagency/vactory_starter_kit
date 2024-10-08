<?php

namespace Drupal\vactory_help_center\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Vactory help center form element.
 *
 * @FormElement("vactory_help_center")
 */
class HelpCenterFormElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_called_class();
    return [
      '#process' => [
        [$class, 'processVactoryHelpCenter'],
      ],
      '#element_validate' => [
        [$class, 'validateVactoryHelpCenter'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the Vactory Help Center form element.
   */
  public static function processVactoryHelpCenter(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['index'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'] ?? '',
      '#description' => t('Help center element'),
    ];
    return $element;
  }

  /**
   * Validates the Vactory Help Center form element.
   */
  public static function validateVactoryHelpCenter(array $element, FormStateInterface $form_state) {
  }

}
