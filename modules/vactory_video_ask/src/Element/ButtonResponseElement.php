<?php

namespace Drupal\vactory_video_ask\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide an Button Response form element.
 *
 * @FormElement("video_ask_button")
 */
class ButtonResponseElement extends FormElement {

  /**
   * Returns the element properties for this element.
   *
   * @return array
   *   An array of element properties. See
   *   \Drupal\Core\Render\ElementInfoManagerInterface::getInfo() for
   *   documentation of the standard properties of all elements, and the
   *   return value format.
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#button_id' => 0,
      '#process' => [
        [$class, 'processButton'],
      ],
      '#element_validate' => [
        [$class, 'validateButton'],
      ],
      '#theme_wrappers' => ['fieldset'],
    ];
  }

  /**
   * Button response form element process callback.
   */
  public static function processButton(&$element, FormStateInterface $form_state, &$form) {
    $default_values = isset($element['#default_value']) ? $element['#default_value'] : NULL;
    
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#required' => TRUE,
      '#default_value' => isset($default_values['label']) && !empty($default_values['label']) ? $default_values['label'] : '',
    ];
    $element['goto'] = [
      '#type' => 'textfield',
      '#title' => t('Go to'),
      '#default_value' => isset($default_values['goto']) && !empty($default_values['goto']) ? $default_values['goto'] : '',
    ];

    return $element;
  }

  /**
   * Button response form element validate callback.
   */
  public static function validateButton(&$element, FormStateInterface $form_state, &$form) {

  }

}
