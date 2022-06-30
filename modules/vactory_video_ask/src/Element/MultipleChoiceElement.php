<?php

namespace Drupal\vactory_video_ask\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide an Multiple choice form element.
 *
 * @FormElement("video_ask_multiple_choice")
 */
class MultipleChoiceElement extends FormElement {

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
      '#multiple_choice_id' => 1,
      '#process' => [
        [$class, 'processMultipleChoice'],
      ],
      '#element_validate' => [
        [$class, 'validateMultipleChoice'],
      ],
      '#theme_wrappers' => ['fieldset'],
    ];
  }

  /**
   * Multiple choices form element process callback.
   */
  public static function processMultipleChoice(&$element, FormStateInterface $form_state, &$form) {
    $default_value = isset($element['#default_value']) ? $element['#default_value'] : '';
    $multiple_choice_id = $element['#multiple_choice_id'];

    $element['allow_multiple'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Multiple'),
      '#default_value' => isset($default_value['allow_multiple']) && !empty($default_value['allow_multiple']) ? $default_value['allow_multiple'] : 0,
    ];

    $element['answers'] = [
      '#type' => 'id_label',
      '#multiple' => TRUE,
      '#cardinality' => -1,
      '#id_label_id' => $multiple_choice_id,
      '#default_value' => (isset($default_value['answers'])) && !empty($default_value['answers']) ? $default_value['answers'] : [],
    ];

    return $element;
  }

  /**
   * Multiple choices form element validate callback.
   */
  public static function validateMultipleChoice(&$element, FormStateInterface $form_state, &$form) {

  }

}
