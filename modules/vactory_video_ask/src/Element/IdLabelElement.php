<?php

namespace Drupal\vactory_video_ask\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide an IdLabel form element.
 *
 * @FormElement("id_label")
 */
class IdLabelElement extends FormElement {

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
      '#id_label_id' => 0,
      '#process' => [
        [$class, 'processIdLabel'],
      ],
      '#element_validate' => [
        [$class, 'validateIdLabel'],
      ],
      '#theme_wrappers' => ['fieldset'],
      '#multiple' => FALSE,
      '#cardinality' => self::CARDINALITY_UNLIMITED,
    ];
  }

  /**
   * Url extended form element process callback.
   */
  public static function processIdLabel(&$element, FormStateInterface $form_state, &$form) {
    $default_values = isset($element['#default_value']) ? $element['#default_value'] : NULL;
    $parents = $element['#parents'];
    $cardinality = $element['#cardinality'];
    $id = $element['#id_label_id'];
    $id_prefix = implode('-', $parents);
    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper-' . $id);
    $element_state = static::getElementState($parents, $form_state);
    if ($element_state === NULL) {
      $element_state = [
        'items_count' => !empty($default_values) ? count($default_values) - 1 : 0,
      ];
      static::setElementState($parents, $form_state, $element_state);
    }
    // Determine the number of elements to display.
    $max = $cardinality === self::CARDINALITY_UNLIMITED ? $element_state['items_count'] : ($cardinality - 1);
    for ($i = 0; $i <= $max; $i++) {
      $element[$i]['title'] = [
        '#type' => 'markup',
        '#markup' => "<h5>Réponse " . ($i + 1) . " : </h5>",
      ];
      $element[$i]['id'] = [
        '#type' => 'textfield',
        '#title' => t('Id'),
        '#required' => TRUE,
        '#default_value' => isset($default_values[$i]['id']) && !empty($default_values[$i]['id']) ? $default_values[$i]['id'] : '',
      ];

      $element[$i]['label'] = [
        '#type' => 'textfield',
        '#title' => t('Label'),
        '#required' => TRUE,
        '#default_value' => isset($default_values[$i]['label']) && !empty($default_values[$i]['label']) ? $default_values[$i]['label'] : '',
      ];

      $element[$i]['goto'] = [
        '#type' => 'textfield',
        '#title' => t('Go to'),
        '#default_value' => isset($default_values[$i]['goto']) && !empty($default_values[$i]['goto']) ? $default_values[$i]['goto'] : '',
      ];

      $element[$i]['is_primary'] = [
        '#type' => 'checkbox',
        '#title' => t('Is Primary'),
        '#default_value' => isset($default_values[$i]['is_primary']) && !empty($default_values[$i]['is_primary']) ? $default_values[$i]['is_primary'] : FALSE,
      ];
    }

    if ($cardinality === self::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {

      $element['#prefix'] = '<div id="' . $wrapper_id . '">';
      $element['#suffix'] = '</div>';
      $element['add_more'] = [
        '#type' => 'submit',
        '#name' => strtr($id_prefix, '-', '_') . '_add_more_' . $id,
        '#value' => "add more",
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
          '#name' => strtr($id_prefix, '-', '_') . '_delete_item_' . $id,
          '#value' => "delete item",
          '#attributes' => ['class' => ['id-label-delete-item-submit']],
          '#submit' => [[static::class, 'deleteItemSubmit']],
          '#limit_validation_errors' => [$element['#array_parents']],
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
   * Delete item ajax call.
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
   * Add more ajax.
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
   * Id label form element validate callback.
   */
  public static function validateIdLabel(&$element, FormStateInterface $form_state, &$form) {

  }

}
