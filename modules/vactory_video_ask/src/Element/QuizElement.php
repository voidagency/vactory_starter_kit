<?php

namespace Drupal\vactory_video_ask\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide an Quiz form element.
 *
 * @FormElement("video_ask_quiz")
 */
class QuizElement extends FormElement {

  const CARDINALITY_UNLIMITED = -1;

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
      '#quiz_id' => 1,
      '#process' => [
        [$class, 'processQuiz'],
      ],
      '#element_validate' => [
        [$class, 'validateQuiz'],
      ],
      '#theme_wrappers' => ['fieldset'],
      '#cardinality' => self::CARDINALITY_UNLIMITED,
    ];
  }

  /**
   * Quiz form element process callback.
   */
  public static function processQuiz(&$element, FormStateInterface $form_state, &$form) {
    $default_value = isset($element['#default_value']) ? $element['#default_value'] : '';
    $quiz_id = $element['#quiz_id'];
    $parents = $element['#parents'];
    $cardinality = $element['#cardinality'];
    $id_prefix = implode('-', $parents);
    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
    $element_state = static::getElementState($parents, $form_state);
    if ($element_state === NULL) {
      if (!empty($default_value)) {
        $element_state = [
          'items_count' => count($default_value) - 1,
        ];
      }
      else {
        $element_state = [
          'items_count' => 0,
        ];
      }
      static::setElementState($parents, $form_state, $element_state);
    }
    // Determine the number of elements to display.
    $max = $cardinality === self::CARDINALITY_UNLIMITED ? $element_state['items_count'] : ($cardinality - 1);
    for ($i = 0; $i <= $max; $i++) {
      $element[$i]['question'] = [
        '#type' => 'textfield',
        '#title' => t('Question'),
        '#required' => TRUE,
        '#default_value' => isset($default_value[$i]['question']) && !empty($default_value[$i]['question']) ? $default_value[$i]['question'] : '',
      ];

      $element[$i]['allow_multiple'] = [
        '#type' => 'checkbox',
        '#title' => t('Allow Multiple'),
        '#default_value' => isset($default_value[$i]['allow_multiple']) && !empty($default_value[$i]['allow_multiple']) ? $default_value[$i]['allow_multiple'] : 0,
      ];

      $element[$i]['answers'] = [
        '#type' => 'id_label',
        '#multiple' => TRUE,
        '#cardinality' => -1,
        '#id_label_id' => $quiz_id,
        '#default_value' => isset($default_value[$i]['answers']) && !empty($default_value[$i]['answers']) ? $default_value[$i]['answers'] : [],
      ];
    }

    if ($cardinality === self::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {

      $element['#prefix'] = '<div id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div>';
      $element['add_more'] = [
        '#type' => 'submit',
        '#name' => strtr($id_prefix, '-', '_') . '_add_more',
        '#value' => "add Question",
        '#attributes' => ['class' => ['id-label-add-more-submit']],
        '#submit' => [[static::class, 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'addMoreAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ];
      if ($max !== 0) {
        $element['delete_item'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_delete_item',
          '#value' => "delete item",
          '#attributes' => ['class' => ['id-label-delete-item-submit']],
          '#submit' => [[static::class, 'deleteItemSubmit']],
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => [static::class, 'addMoreAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }

    }

    return $element;
  }

  /**
   * Quiz form element validate callback.
   */
  public static function validateQuiz(&$element, FormStateInterface $form_state, &$form) {

  }

  /**
   * Get element state.
   */
  public static function getElementState(array $parents, FormStateInterface $form_state): ?array {
    return NestedArray::getValue($form_state->getStorage(), $parents);
  }

  /**
   * Set element state.
   */
  public static function setElementState(array $parents, FormStateInterface $form_state, array $field_state): void {
    NestedArray::setValue($form_state->getStorage(), $parents, $field_state);
  }

  /**
   * Add more ajax call.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];

    // Increment the items count.
    $element_state = static::getElementState($parents, $form_state);
    $element_state['items_count']++;
    static::setElementState($parents, $form_state, $element_state);

    $form_state->setRebuild();
  }

  /**
   * Delete item ajax.
   */
  public static function deleteItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];

    // Increment the items count.
    $element_state = static::getElementState($parents, $form_state);
    $element_state['items_count']--;
    static::setElementState($parents, $form_state, $element_state);

    $form_state->setRebuild();
  }

  /**
   * Add more items.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state): ?array {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    // Ensure the widget allows adding additional items.
    if ($element['#cardinality'] != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return NULL;
    }

    return $element;
  }

}
