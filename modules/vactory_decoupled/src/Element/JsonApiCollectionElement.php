<?php

namespace Drupal\vactory_decoupled\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\entityqueue\Entity\EntityQueue;

/**
 * Provide a JSON API form element for retieving data collection from JSON:API.
 *
 * @FormElement("json_api_collection")
 */
class JsonApiCollectionElement extends FormElement {

  const DELIMITER = ',';

  /**
   * {@inheritDoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#default_value' => [],
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

    $has_access = \Drupal::currentUser()
      ->hasPermission('administer field views dynamic field settings');
    $has_access_administer_entityqueue = \Drupal::currentUser()
      ->hasPermission('administer entityqueue');

    $element['resource'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#description' => t('Select a JSON:API resource'),
      '#title' => t('JSON:API Resource'),
      '#empty_option' => t('- Select -'),
      '#options' => self::getJsonApiResources(),
      '#default_value' => $element['#default_value']['resource'] ?? '',
      '#wrapper_attributes' => [
        'style' => $has_access ? NULL : 'display:none',
      ],
      '#attributes' => [
        'style' => $has_access ? NULL : 'display:none',
      ],
    ];

    $element['filters'] = [
      '#type' => 'textarea',
      '#title' => t('JSON:API Fields'),
      '#placeholder' =>
      'fields[node--vactory_news]=drupal_internal__nid,title,field_vactory_news_theme,field_vactory_media' . "\n" .
      'fields[taxonomy_term--vactory_news_theme]=tid,name' . "\n" .
      'fields[media--image]=name,thumbnail' . "\n" .
      'fields[file--image]=filename,uri'. "\n" .
      'include=field_vactory_news_theme,field_vactory_media,field_vactory_media.thumbnail' . "\n" .
      'filter[category][condition][path]=field_vactory_news_theme.drupal_internal__tid' . "\n" .
      'filter[category][condition][operator]=%3D  <- encoded "=" symbol' . "\n" .
      'filter[category][condition][value]=3',
      '#description' => t('Used to filter, paginate, sort and select which fields to return from the results. Enter each value per line'),
      '#default_value' => implode("\n", $element['#default_value']['filters']),
      '#wrapper_attributes' => [
        'style' => $has_access ? NULL : 'display:none',
      ],
    ];

    $element['entity_queue'] = [
      '#type' => 'select',
      '#description' => t('Use an already made queue or prefiltered one to load the nodes you need. <em>Choosing a queue will ignore the filters above.</em> '),
      '#title' => t('Entity Queue'),
      '#empty_option' => t('- Select -'),
      '#options' => self::getEntityQueues(),
      '#default_value' => $element['#default_value']['entity_queue'] ?? '',
      '#wrapper_attributes' => [
        'style' => $has_access_administer_entityqueue ? NULL : 'display:none',
      ],
      '#attributes' => [
        'style' => $has_access_administer_entityqueue ? NULL : 'display:none',
        'id' => 'field_json_api_entity_queue',
      ],
    ];

    $element['entity_queue_field_id'] = [
      '#type' => 'textfield',
      '#title' => t('Entity Queue - Field ID'),
      '#placeholder' => 'drupal_internal__nid',
      '#description' => t('The selected entity queue will return IDs as a result. Use this field match against them. Example: drupal_internal__nid'),
      '#default_value' => $element['#default_value']['entity_queue_field_id'] ??'',
      '#wrapper_attributes' => [
        'style' => $has_access ? NULL : 'display:none',
      ],
      '#states' => [
        'required' => [
          ':input[id="field_json_api_entity_queue"]' => ['!value' => ''],
        ],
        'visible' => [
          ':input[id="field_json_api_entity_queue"]' => ['!value' => ''],
        ],
      ],
    ];

    $element['vocabularies'] = [
      '#type' => 'checkboxes',
      '#title' => t('Exposed Vocabularies'),
      '#description' => t('Terms in these vocabularies will be exposed by the API.'),
      '#options' => self::getVocabularyBundles(),
      '#default_value' => $element['#default_value']['vocabularies'] ?? [],
      '#wrapper_attributes' => [
        'style' => $has_access ? NULL : 'display:none',
      ],
      '#attributes' => [
        'style' => $has_access ? NULL : 'display:none',
      ],
    ];

    return $element;
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
    // if ($input !== FALSE && $input !== NULL) {
    //   if (isset($input['filters']) && !empty($input['filters'])) {
    //     $parsed = [];
    //     $lines = array_map('trim', explode("\n", $input['filters']));
    //     foreach ($lines as $line) {
    //       if ($line) {
    //         [$name, $value] = explode("=", $line, 2);
    //         $parsed[trim($name)] = urldecode(trim($value));
    //       }
    //     }
    //     $input['filters'] = $parsed;
    //   }
    //   else {
    //     $input['filters'] = []; 
    //   }
    // }
    // else {
    //   $input['filters'] = []; 
    // }

    return is_array($input) ? $input : $element['#default_value'];
  }

  /**
   * The entity queue list to use in options.
   *
   * @return array
   *   The entity queue list.
   */
  protected static function getEntityQueues(): array {
    $options = [];
    $storage = \Drupal::entityTypeManager()->getStorage('entity_queue');

    $queue_ids = $storage->getQuery()
      ->condition('status', TRUE)
      ->execute();

    $queues = $storage->loadMultiple($queue_ids);

    foreach ($queues as $queue) {
      $options[$queue->id()] = $queue->label();
    }

    return $options;
  }

  /**
   * The taxonomy terms bundle list to use in checkboxes options.
   *
   * @return array
   *   The taxonomy terms bundle list.
   */
  protected static function getVocabularyBundles(): array {
    $bundle_options = [];
    $bundles = \Drupal::service('entity_type.bundle.info')
      ->getBundleInfo('taxonomy_term');
    foreach ($bundles as $bundle_id => $bundle) {
      $bundle_options[$bundle_id] = $bundle['label'];
    }

    return $bundle_options;
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
      $options[$resource_type->getTypeName()] = $resource_type->getTypeName();
     }

    return $options;
  }

}
