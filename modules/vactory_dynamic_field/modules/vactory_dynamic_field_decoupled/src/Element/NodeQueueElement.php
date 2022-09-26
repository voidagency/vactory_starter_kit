<?php

namespace Drupal\vactory_dynamic_field_decoupled\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\node\Entity\Node;

/**
 * Provide a JSON API form element for retieving data collection from JSON:API.
 *
 * @FormElement("node_queue")
 */
class NodeQueueElement extends FormElement {

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
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Element process callback.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;
    $default_value = isset($element['#default_value']) ? $element['#default_value'] : '';
    $selector = $element['#attributes']['data-drupal-selector'];
    preg_match_all('!\d+!', $selector, $matches);
    $weight = $matches[0][0];
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    if (!isset($element_state['nodes'][$weight]['resource'])) {
      $element_state['nodes'][$weight]['resource'] = $element['#default_value']['resource'] != '' ? $element['#default_value']['resource'] : '';
      static::setElementState($parents, $form_state, $element_state);
    }
    $resource = isset($element_state['nodes'][$weight]['resource']) ? $element_state['nodes'][$weight]['resource'] : '';

    $element['#prefix'] = "<div id='df_decoupled_nodes_$weight'>";
    $element['#suffix'] = '</div>';

    $element['resource'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#description' => t('Select a JSON:API resource'),
      '#title' => t('JSON:API Resource'),
      '#empty_option' => t('- Select -'),
      '#options' => self::getJsonApiResources(),
      '#default_value' => $resource,
      '#ajax' => [
        'callback' => [static::class, 'onResourceChange'],
        'wrapper' => "df_decoupled_nodes_$weight",
        'weight' => $weight,
      ],
    ];

    $element['update_resource'] = [
      '#type'                    => 'submit',
      '#value'                   => t('Update widget'),
      '#name' => 'update_resource_' . $weight,
      '#ajax'                    => [
        'callback' => [static::class, 'updateResourceCallBack'],
        'wrapper'  => "df_decoupled_nodes_$weight",
        'event'    => 'click',
      ],
      '#attributes' => [
        'style' => ['display:none;'],
      ],
      '#limit_validation_errors' => [],
      '#submit' => [[static::class, 'updateResource']],
    ];

    $filters_default_value = 'fields[node--vactory_news]=drupal_internal__nid,title,field_vactory_news_theme,field_vactory_media' . "\n" .
      'fields[taxonomy_term--vactory_news_theme]=tid,name' . "\n" .
      'fields[media--image]=name,thumbnail' . "\n" .
      'fields[file--image]=filename,uri'. "\n" .
      'include=field_vactory_news_theme,field_vactory_media,field_vactory_media.thumbnail';

    $filters = isset($default_value['filters']) && $default_value['filters'] != '' ? $default_value['filters'] : $filters_default_value;

    $element['filters'] = [
      '#type' => 'textarea',
      '#title' => t('JSON:API Fields'),
      '#description' => t('Used to filter, paginate, sort and select which fields to return from the results. Enter each value per line'),
      '#default_value' => is_array($filters) ? implode("\n", $filters) : $filters,
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
          'target_bundles' => [str_replace('node--', '', $resource)]
        ],
        '#tags' => TRUE,
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
    $element_state['nodes'][$weight]['resource'] = $values['components'][$weight]['nodes']['resource'];
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
    if ($input !== FALSE && $input !== NULL && isset($input['filters']) && !empty($input['filters'])) {
      $input['filters'] = array_map('trim', explode("\n", $input['filters']));
    }
    return is_array($input) ? $input : $element['#default_value'];
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

  /**
   * The json:api resources list to use in options.
   *
   * @return array
   *   The enabled json:api resources list.
   */
  protected static function getJsonApiResources(): array {
    $options = [];

    /** @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType[] $resource_types */
    $resource_types = \Drupal::service('jsonapi.resource_type.repository')->all();
    foreach ($resource_types as $resource_type) {
      /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource_config */
      $resource_config = $resource_type->getJsonapiResourceConfig();

      if ($resource_config->get('disabled')) {
        continue;
      }
      if (strpos($resource_type->getTypeName(), 'node--') === 0) {
        $options[$resource_type->getTypeName()] = $resource_type->getTypeName();
      }
    }

    return $options;
  }


}
