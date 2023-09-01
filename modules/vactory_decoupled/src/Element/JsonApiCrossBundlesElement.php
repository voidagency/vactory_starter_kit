<?php

namespace Drupal\vactory_decoupled\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jsonapi_cross_bundles\ResourceType\CrossBundlesResourceType;

/**
 * Provide a JSON API form element for multiple bundles from JSON:API.
 *
 * @FormElement("json_api_cross_bundles")
 */
class JsonApiCrossBundlesElement extends JsonApiCollectionElement {

  const DELIMITER = ',';

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

    $has_access = \Drupal::currentUser()
      ->hasPermission('administer field views dynamic field settings');

    $element = parent::processElement($element, $form_state, $complete_form);
    $id = str_replace(['[', ']'], ['-', ''], $element['#name']);
    $element['resource'] = [
      '#type'       => 'fieldset',
      '#title'      => $element['#title'] ?? '',
      '#collapsed'  => TRUE,
      '#attributes' => [
        'id' => $id,
      ],
    ];

    $value = $element['#default_value'] ?? [];

    $entity_type_definitions = \Drupal::entityTypeManager()->getDefinitions();
    $entity_type_definitions = array_map(function ($definition) {
      return $definition->getLabel();
    }, $entity_type_definitions);

    $element['resource']['entity_type'] = [
      '#type'               => 'select',
      '#required'           => TRUE,
      '#description'        => t('Select a JSON:API resource'),
      '#title'              => t('JSON:API Resource'),
      '#empty_option'       => t('- Select -'),
      '#options'            => self::getJsonApiResources(),
      '#default_value'      => $value['resource']['entity_type'] ?? '',
      '#wrapper_attributes' => [
        'style' => $has_access ? NULL : 'display:none',
      ],
      '#attributes'         => [
        'style'      => $has_access ? NULL : 'display:none',
        'element-id' => $id,
      ],
      '#ajax'               => [
        'callback' => [static::class, 'triggerEntityReferenceUpdate'],
      ],
    ];

    $element['resource']['submit'] = [
      '#type'       => 'submit',
      '#value'      => t('update entity reference'),
      '#name'       => $id,
      '#submit'     => [[static::class, 'entityReferenceElementSubmit']],
      '#attributes' => [
        'style' => ['display:none;'],
      ],
      '#ajax'       => [
        'callback' => [static::class, 'updateEntityReferenceElement'],
        'wrapper'  => $id,
        'event'    => 'click',
      ],
    ];

    $parents = $element['#parents'];
    $bundles = [];
    if (!isset($element_state) && isset($value['resource']['entity_type'])) {
      $bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($value['resource']['entity_type']);
      $bundles = array_map(function ($bundle) {
        return $bundle['label'];
      }, $bundles);
    }
    $element_state = static::getElementState($parents, $form_state);
    $bundles = $element_state['resource']['entity_type']['bundles'] ?? $bundles;
    if (!empty($bundles)) {
      $element['resource']['bundle'] = [
        '#type'          => 'checkboxes',
        '#title'         => t('Concerned entities'),
        '#options'       => $bundles,
        '#default_value' => $value['resource']['bundle'] ?? '',
      ];
    }

    return $element;

  }

  /**
   * Ajax Callback.
   */
  public static function bundlesCallback($form, FormStateInterface $form_state) {
    return $form['json_api_cross_bundles']['container'];
  }

  /**
   * Get json api cross bundles resources.
   */
  protected static function getJsonApiResources(): array {

    $options = [];

    /** @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType[] $resource_types */
    $resource_types = \Drupal::service('jsonapi.resource_type.repository')
      ->all();
    foreach ($resource_types as $resource_type) {

      if ($resource_type instanceof CrossBundlesResourceType) {
        $options[$resource_type->getTypeName()] = $resource_type->getTypeName();
      }
    }
    return $options;
  }

  /**
   * Callback Trigger Entity Reference Update.
   */
  public static function triggerEntityReferenceUpdate($form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $name = $triggering_element['#attributes']['element-id'];
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("[name=$name]", 'trigger', ['click']));
    return $response;
  }

  /**
   * Callback Entity Reference Element Submit.
   */
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

  /**
   * Callback Update Entity Reference Element.
   */
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

  /**
   * {@inheritdoc}
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // Add element validation here.
  }

}
