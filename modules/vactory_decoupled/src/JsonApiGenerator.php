<?php

namespace Drupal\vactory_decoupled;

// use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\Core\Cache\Cache;

/**
 * Simplifies the process of generating an API version using DF.
 *
 * @api
 */
class JsonApiGenerator {

  protected $client;

  /**
   * {@inheritdoc}
   */
  public function __construct(JsonApiClient $client) {
    $this->client = $client;
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param array $config
   *   The Config settings; see FormElement("dynamic_views")
   *
   * @return array
   *   The JSON structure of the requested resource.
   *
   */
  public function fetch(array $config) {
    $id = $config['id'] ?? '';
    $resource = $config['resource'];
    $filters = $config['filters'];
    $exposed_vocabularies = $config['vocabularies'];
    $entity_queue = $config['entity_queue'] ?? '';
    $entity_queue_field_id = $config['entity_queue_field_id'] ?? '';
    $subqueue_items_ids = [];

    // Handle jsonapi filters tokens.
    $nested_filters = [];
    foreach ($filters as $filter) {
      if (strpos($filter, '[') === 0 && strpos($filter, ']') === strlen($filter)-1) {
        // Token case.
        $filter = \Drupal::token()->replace($filter, []);
        $nested_filters = [...$nested_filters, ...explode("\n", $filter)];
      }
    }
    $filters = [...$filters, ...$nested_filters];

    // Filters may be altered using hook_json_api_collection_alter which is triggered below.
    // We need to keep a copy of the original filters to be used
    // by the frontend Component.
    // The client component only care about what has been set in the DF yml setting.
    $original_filters = $filters;

    // Add a filter for entity queue.
    if (!empty($entity_queue)) {
      $subqueue = EntitySubqueue::load($entity_queue);
      $subqueue_items = $subqueue->get('items')->getValue();
      $subqueue_items_ids = array_map(function ($item) {
        return $item['target_id'];
      }, $subqueue_items);

      if (count($subqueue_items_ids) > 0) {
        $filters[] = "filter[_subqueue][condition][path]=" . $entity_queue_field_id;
        $filters[] = "filter[_subqueue][condition][operator]=IN";

        $i = 1;
        foreach ($subqueue_items_ids as $id) {
          $filters[] = 'filter[_subqueue][condition][value][' . $i . ']=' . $id;
          $i++;
        }
      }
    }

    $parsed = [];
    foreach ($filters as $line) {
      [$name, $qsvalue] = explode("=", $line, 2);
      $parsed[trim($name)] = urldecode(trim(\Drupal::token()
        ->replace($qsvalue, [])));
    }

    $original_filters_parsed = [];
    foreach ($original_filters as $line) {
      [$name, $qsvalue] = explode("=", $line, 2);
      $original_filters_parsed[trim($name)] = urldecode(trim(\Drupal::token()
        ->replace($qsvalue, [])));
    }

    /*
      * Allow other modules to override json_api_collection filters.
      *
      * @code
      * Implements hook_json_api_collection_alter().
      * function myModule_json_api_collection_alter(&$filters, &$context) {
      *   $query = \Drupal::request()->query->get("q");
      *   $id = $context['id'];
      *   ... do something, like altering the filters
      * }
      * @endcode
      */

    $hook_context = [
      'id' => $id,
    ];

    // Get current page information and pass them through the hook context.
    $params = \Drupal::routeMatch()->getParameters();
    if ($params) {
      if ($resource_type_param = $params->get('resource_type')) {
        $hook_context["entity_bundle"] = $resource_type_param->getBundle();
      }

      if ($entity_param = $params->get('entity')) {
        $hook_context["entity_id"] = $entity_param->id();
      }
    }
    $parsed['optional_filters_data'] = $config['optional_filters_data'] ?? [];
    \Drupal::moduleHandler()
      ->alter('json_api_collection', $parsed, $hook_context);
    unset($parsed['optional_filters_data']);
    parse_str(http_build_query($parsed), $query_filters);
    parse_str(http_build_query($original_filters_parsed), $query_original_filters);

    $response = $this->client->serialize($resource, $query_filters);
    $exposedTerms = $this->getExposedTerms($exposed_vocabularies);
    $response['cache']['tags'] = Cache::mergeTags($response['cache']['tags'], $exposedTerms['cache_tags']);
    $response['cache']['contexts'] = ['languages:language_interface', 'languages:language_url'];

    $client_data = json_decode($response['data']);

    // For entityqueue, we cannot use JSON:API sorting mecanism as we don't have any field attached to entities
    // where it indicate a sorting value we can use. And since entity queues are limited to fewer items or we assume so.
    // we are going to alter the response and do a dynamic sorting.
    // @todo: we should only trigger if the results are less then 50 as it harcoded in JSON:API max return list
    // we don't wanna mess with the order of the rest of the pages.
    if (!empty($entity_queue) && count($subqueue_items_ids) > 0) {
      $items = $client_data->data ?? [];
      $result = array_map(static fn($entity_queue_id) => current(array_values(
        array_filter($items, static fn($entity) => intval($entity->attributes->{$entity_queue_field_id}) === intval($entity_queue_id))
        )), $subqueue_items_ids);
      $result = array_filter($result, function ($e) {
        return $e; //when this value is false the element is removed.
      });
      $client_data->data = array_values($result);
    }

    return [
      'data' => $client_data,
      'cache' => $response['cache'],
      'filters' => $query_filters,
      'original_filters' => $query_original_filters,
      'taxonomies' => $exposedTerms['data'],
    ];
  }

  protected function getExposedTerms(array $vocabularies) {
    $result = [];
    $cacheTags = [];

    $entityTypeManager = \Drupal::service('entity_type.manager');
    $taxonomyTermStorage = $entityTypeManager->getStorage('taxonomy_term');
    // $slugManager = \Drupal::service('vactory_core.slug_manager');
    $entityRepository = \Drupal::service('entity.repository');
    $bundles = (array) $vocabularies;
    $bundles = array_filter($bundles, function ($value) {
      return $value != '0';
    });
    $bundles = array_keys($bundles);

    foreach ($bundles as $vid) {
      $terms = $taxonomyTermStorage->loadTree($vid, 0, NULL, TRUE);
      $result[$vid] = [];
      foreach ($terms as $term) {
        $term = $entityRepository
          ->getTranslationFromContext($term);

        $cacheTags = Cache::mergeTags($cacheTags, $term->getCacheTags());
        array_push($result[$vid], [
          'id' => $term->id(),
          'uuid' => $term->uuid(),
          'slug' => $term->get("term_2_slug")->getString(),
          'label' => $term->label(),
        ]);
      }

    }

    return [
      "data" => $result,
      "cache_tags" => $cacheTags,
    ];
  }

}
