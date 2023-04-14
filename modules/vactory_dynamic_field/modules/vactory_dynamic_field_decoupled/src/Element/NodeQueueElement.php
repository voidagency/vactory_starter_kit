<?php

namespace Drupal\vactory_dynamic_field_decoupled\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\vactory_decoupled\Element\JsonApiCollectionElement;

/**
 * Provide a JSON API form element for retieving data collection from JSON:API.
 *
 * @FormElement("node_queue")
 */
class NodeQueueElement extends JsonApiCollectionElement {

  /**
   * {@inheritDoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
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
    $selector = $element['#attributes']['data-drupal-selector'];
    preg_match_all('!\d+!', $selector, $matches);
    $weight = $matches[0][0];
    $element['#prefix'] = "<div id='df_decoupled_nodes_$weight'>";
    $element['#suffix'] = '</div>';
    parent::processElement($element, $form_state, $complete_form);

    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    if (!isset($element_state['nodes'][$weight]['resource'])) {
      $element_state['nodes'][$weight]['resource'] = $element['#default_value']['resource'] != '' ? $element['#default_value']['resource'] : '';
      static::setElementState($parents, $form_state, $element_state);
    }
    $resource = isset($element_state['nodes'][$weight]['resource']) ? $element_state['nodes'][$weight]['resource'] : $element['#default_value']['resource'];

    $element['entity_queue']['#access'] = FALSE;
    $element['entity_queue_field_id']['#access'] = FALSE;
    $element['vocabularies']['#access'] = FALSE;
    $element['id']['#access'] = FALSE;
    $element['resource']['#ajax'] = [
      'callback' => [static::class, 'onResourceChange'],
      'wrapper' => "df_decoupled_nodes_$weight",
      'weight' => $weight,
    ];

    $element['update_resource'] = [
      '#type' => 'submit',
      '#value' => t('Update widget'),
      '#name' => 'update_resource_' . $weight,
      '#ajax' => [
        'callback' => [static::class, 'updateResourceCallBack'],
        'wrapper' => "df_decoupled_nodes_$weight",
        'event' => 'click',
      ],
      '#attributes' => [
        'style' => ['display:none;'],
      ],
      '#limit_validation_errors' => [],
      '#submit' => [[static::class, 'updateResource']],
    ];

    $nodes_default_values = $element['#default_value']['nodes'] ?? [];
    $nodes = [];
    if ($nodes_default_values != []) {
      foreach ($nodes_default_values as $value) {
        $node = Node::load($value['target_id']);
        if (isset($node)) {
          $nodes[] = $node;
        }
      }
    }

    if ($resource != '') {
      $element['nodes'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Nodes'),
        '#target_type' => 'node',
        '#required' => TRUE,
        '#selection_settings' => [
          'target_bundles' => [str_replace('node--', '', $resource)],
        ],
        '#tags' => TRUE,
        '#maxlength' => NULL,
        '#default_value' => $nodes,
      ];
    }

    return $element;
  }

  /**
   * Update widget layout background callback.
   */
  public static function updateResourceCallBack(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

  /**
   * Update items layout background callback.
   */
  public static function updateResource(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $button['#name'], $matches);
    $weight = $matches[0][0];
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state) ?? [];
    $values = $form_state->getUserInput();
    $element_state['nodes'][$weight]['resource'] = $values['components'][$weight]['collection']['resource'];
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * On change layout function.
   */
  public static function onResourceChange(array $form, FormStateInterface $form_state) {
    $select = $form_state->getTriggeringElement();
    $weight = $select['#ajax']['weight'];
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("[name=update_resource_$weight]", 'trigger', ['click']));
    return $response;
  }

  /**
   * Form element validate callback.
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$form) {
    if ($element['#required'] && $element['#value'] == '_none') {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return parent::valueCallback($element, $input, $form_state);
  }

  /**
   * Get element state.
   */
  public static function getElementState(array $parents, FormStateInterface $form_state) {
    return NestedArray::getValue($form_state->getStorage(), $parents);
  }

  /**
   * Set element state.
   */
  public static function setElementState(array $parents, FormStateInterface $form_state, array $field_state) {
    NestedArray::setValue($form_state->getStorage(), $parents, $field_state);
  }

}
