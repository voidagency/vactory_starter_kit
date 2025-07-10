<?php

namespace Drupal\vactory_satisfaction\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Component\Utility\Html;

/**
 * Satisfaction form element.
 *
 * @FormElement("vactory_satisfaction")
 */
class SatisfactionFormElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_called_class();
    return [
      '#process' => [
        [$class, 'processVactorySatisfaction'],
      ],
      '#element_validate' => [
        [$class, 'validateVactorySatisfaction'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the Vactory Satisfaction form element.
   */
  public static function processVactorySatisfaction(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $default_value = isset($element['#default_value']) && is_array($element['#default_value']) ? $element['#default_value'] : [];

    $id_prefix = implode('-', $element['#parents']);
    $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $button_id_prefix = implode('_', $element['#parents']);

    $element_state = self::getElementState($element['#parents'], $form_state);
    if (!isset($element_state['options'])) {
      if (isset($default_value['options']) && is_array($default_value['options'])) {
        $default_value['options'] = array_values($default_value['options']);
        $element_state['options'] = count($default_value['options']);
      }
      else {
        $element_state['options'] = 1;
      }
      self::setElementState($element['#parents'], $form_state, $element_state);
    }
    if (!isset($element_state['removed_indexes'])) {
      $element_state['removed_indexes'] = [];
      self::setElementState($element['#parents'], $form_state, $element_state);
    }

    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ] + $element;

    $element['options'] = [
      '#type' => 'table',
      '#header' => [
        t('Options'),
        t('Operations'),
      ],
      '#input' => FALSE,
    ];

    for ($i = 0; $i < $element_state['options']; $i++) {
      if (in_array($i, $element_state['removed_indexes'], TRUE)) {
        continue;
      }

      $option_form = &$element['options'][$i];
      $option_form['container'] = [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'display: flex; justify-content: space-around',
        ],
      ];

      $default_container = &$option_form['container'];
      $default_container['image'] = [
        '#type' => 'media_library',
        '#allowed_bundles' => ['image'],
        '#title' => t('Upload your image'),
        '#default_value' => $default_value['options'][$i]['container']['image'] ?? '',
        '#description' => t('Upload or select the image.'),
      ];
      $default_container['icon'] = [
        '#title' => 'Icon',
        '#type' => 'vactory_icon_picker',
        '#default_value' => $default_value['options'][$i]['container']['icon'] ?? '',
      ];
      $default_container['sub_container'] = [
        '#type' => 'container',
      ];

      $sub_container = &$default_container['sub_container'];
      $sub_container['text'] = [
        '#title' => 'Text',
        '#type' => 'textfield',
        '#default_value' => $default_value['options'][$i]['container']['sub_container']['text'] ?? '',
      ];
      $sub_container['display_text'] = [
        '#title' => 'Display title',
        '#type' => 'checkbox',
        '#default_value' => $default_value['options'][$i]['container']['sub_container']['display_text'] ?? '',
      ];
      $sub_container['description'] = [
        '#title' => 'Description',
        '#type' => 'textarea',
        '#default_value' => $default_value['options'][$i]['container']['sub_container']['description'] ?? '',
      ];
      $sub_container['display_description'] = [
        '#title' => 'Display description',
        '#type' => 'checkbox',
        '#default_value' => $default_value['options'][$i]['container']['sub_container']['display_description'] ?? '',
      ];

      $option_form['remove'] = [
        '#type' => 'submit',
        '#name' => $button_id_prefix . '_remove_option' . $i,
        '#value' => t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'removeOptionSubmit']],
        '#option_index' => $i,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }

    $element['add_option'] = [
      '#type' => 'submit',
      '#name' => $button_id_prefix . '_add_option',
      '#value' => t('Add Option'),
      '#submit' => [[get_called_class(), 'addOptionSubmit']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];

    return $element;
  }

  /**
   * Validates the Vactory Satisfaction form element.
   */
  public static function validateVactorySatisfaction(array $element, FormStateInterface $form_state) {
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (in_array('remove', $triggering_element['#array_parents'], TRUE)) {
      // Go 3 levels up in the form, to the widgets container.
      return NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -3));
    }
    return NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
  }

  /**
   * Submit callback for adding a new option.
   */
  public static function addOptionSubmit(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element_parents = array_slice($triggering_element['#parents'], 0, -1);
    $element_state = self::getElementState($element_parents, $form_state);
    $element_state['options']++;
    self::setElementState($element_parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing an option.
   */
  public static function removeOptionSubmit(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element_parents = array_slice($triggering_element['#parents'], 0, -3);
    $element_state = self::getElementState($element_parents, $form_state);
    $territory_index = $triggering_element['#option_index'];
    $element_state['removed_indexes'][] = $territory_index;
    self::setElementState($element_parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Gets the element state.
   */
  public static function getElementState(array $parents, FormStateInterface $form_state) {
    $parents = array_merge(['element_state', '#parents'], $parents);
    return NestedArray::getValue($form_state->getStorage(), $parents);
  }

  /**
   * Sets the element state.
   */
  public static function setElementState(array $parents, FormStateInterface $form_state, array $element_state) {
    $parents = array_merge(['element_state', '#parents'], $parents);
    NestedArray::setValue($form_state->getStorage(), $parents, $element_state);
  }

}
