<?php

namespace Drupal\vactory_decoupled\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide the decoupled entity reference select form element.
 *
 * @FormElement("decoupled_entity_reference")
 */
class DecoupledEntityReferenceSelectElement extends FormElement {

  /**
   * {@inheritDoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#default_value' => [],
      '#title' => '',
      '#process' => [
        [$class, 'processElement'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Element process callback.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;
    $id = str_replace(['[', ']'], ['-', ''], $element['#name']);
    $element['entity_reference'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'] ?? '',
      '#collapsed' => TRUE,
      '#attributes' => [
        'id' => $id,
      ],
    ];

    $value = $element['#default_value'] ?? [];

    $entity_type_definitions = \Drupal::entityTypeManager()->getDefinitions();
    $entity_type_definitions = array_map(function ($definition) {
      return $definition->getLabel();
    }, $entity_type_definitions);
    $element['entity_reference']['entity_type'] = [
      '#type' => 'select',
      '#title' => t('Entity types'),
      '#options' => $entity_type_definitions,
      '#empty_option' => '- Select -',
      '#required' => TRUE,
      '#default_value' => $value['entity_reference']['entity_type'] ?? '',
      '#attributes' => [
        'element-id' => $id,
      ],
      '#ajax' => [
        'callback' => [static::class, 'triggerEntityReferenceUpdate'],
      ],
    ];

    $element['entity_reference']['submit'] = [
      '#type' => 'submit',
      '#value' => t('update entity reference'),
      '#name' => $id,
      '#submit' => [[static::class, 'entityReferenceElementSubmit']],
      '#attributes' => [
        'style' => ['display:none;'],
      ],
      '#ajax' => [
        'callback' => [static::class, 'updateEntityReferenceElement'],
        'wrapper' => $id,
        'event' => 'click',
      ],
    ];

    $parents = $element['#parents'];
    $bundles = [];
    if (!isset($element_state) && isset($value['entity_reference']['entity_type'])) {
      $bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($value['entity_reference']['entity_type']);
      $bundles = array_map(function ($bundle) {
        return $bundle['label'];
      }, $bundles);
    }
    $element_state = static::getElementState($parents, $form_state);
    $bundles = $element_state['entity_reference']['entity_type']['bundles'] ?? $bundles;
    if (!empty($bundles)) {
      $element['entity_reference']['bundle'] = [
        '#type' => 'select',
        '#title' => t('Concerned entity'),
        '#options' => $bundles,
        '#required' => TRUE,
        '#default_value' => $value['entity_reference']['bundle'] ?? '',
      ];
    }

    return $element;
  }

  public static function triggerEntityReferenceUpdate($form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $name = $triggering_element['#attributes']['element-id'];
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("[name=$name]", 'trigger', ['click']));
    return $response;
  }

  public static function entityReferenceElementSubmit($form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    array_pop($parents);
    array_push($parents, 'entity_type');
    $entity_type = $form_state->getValue($parents, '');
    if ($entity_type) {
      $bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($entity_type);
      $bundles = array_map(function ($bundle) {
        return $bundle['label'];
      }, $bundles);
      $element_state = static::getElementState($parents, $form_state);
      $element_state['bundles'] = $bundles;
      static::setElementState($parents, $form_state, $element_state);
      $form_state->setRebuild();
    }
  }

  public static function updateEntityReferenceElement($form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    $element = NestedArray::getValue($form, array_slice($parents, 0, -1));
    return $element;
  }

  /**
   * Get the element state function.
   */
  public static function getElementState(array $parents, FormStateInterface $form_state): ?array {
    return NestedArray::getValue($form_state->getStorage(), $parents);
  }

  /**
   * Set the element state function.
   */
  public static function setElementState(array $parents, FormStateInterface $form_state, array $field_state): void {
    NestedArray::setValue($form_state->getStorage(), $parents, $field_state);
  }

}
