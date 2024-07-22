<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\Core\Cache\Cache;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Simplifies the process of generating an API version using DF.
 *
 * @api
 */
class JsonApiGenerator {

  /**
   * Json api client.
   *
   * @var JsonApiClient
   */
  protected $client;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Drupal token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * Term result Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termResultStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    JsonApiClient $client,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    Token $token,
    ModuleHandlerInterface $moduleHandler,
    RouteMatchInterface $routeMatch
  ) {
    $this->client = $client;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->token = $token;
    $this->moduleHandler = $moduleHandler;
    $this->routeMatch = $routeMatch;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    if ($this->moduleHandler->moduleExists('vactory_taxonomy_results')) {
      $this->termResultStorage = $this->entityTypeManager->getStorage('term_result_count');
    }
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param array $config
   *   The Config settings; see FormElement("dynamic_views")
   *
   * @return array
   *   The JSON structure of the requested resource.
   */
  public function fetch(array $config) {
    $id = $config['id'] ?? '';
    $resource = $config['resource'];
    $filters = $config['filters'];
    $exposed_vocabularies = $config['vocabularies'] ?? [];
    $entity_queue = $config['entity_queue'] ?? '';
    $entity_queue_field_id = $config['entity_queue_field_id'] ?? '';
    $cache_tags = !empty($config['cache_tags']) ? explode("\n", $config['cache_tags']) : [];
    $cache_contexts = !empty($config['cache_contexts']) ? explode("\n", $config['cache_contexts']) : [];
    $subqueue_items_ids = [];

    // Handle jsonapi filters tokens.
    $nested_filters = [];
    foreach ($filters as $key => $filter) {
      if (strpos($filter, '[') === 0 && strpos($filter, ']') === strlen($filter) - 1) {
        // Token case.
        $filter = $this->token->replace($filter, []);
        $filter_pieces = is_string($filter) ? explode("\n", $filter) : [];
        $nested_filters = [...$nested_filters, ...$filter_pieces];
        unset($filters[$key]);
      }
    }
    $filters = [...$filters, ...$nested_filters];

    // Filters may be altered using hook_json_api_collection_alter.
    // which is triggered below.
    // We need to keep a copy of the original filters to be used.
    // by the frontend Component.
    // The client component only care about.
    // What has been set in the DF yml setting.
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
      $parsed[trim($name)] = urldecode(trim($this->token->replace($qsvalue, [])));
    }

    $original_filters_parsed = [];
    foreach ($original_filters as $line) {
      [$name, $qsvalue] = explode("=", $line, 2);
      $original_filters_parsed[trim($name)] = urldecode(trim($this->token->replace($qsvalue, [])));
    }

    /*
     * Allow other modules to override json_api_collection filters.
     *
     * @code
     * Implements hook_json_api_collection_alter().
     * function myModule_json_api_collection_alter(&$filters, &$context) {
     *   $query = \Drupal::request()->query->all("q");
     *   $id = $context['id'];
     *   ... do something, like altering the filters
     * }
     * @endcode
     */

    $hook_context = [
      'id' => $id,
    ];

    // Get current page information and pass them through the hook context.
    $params = $this->routeMatch->getParameters();
    if ($params) {
      if ($resource_type_param = $params->get('resource_type')) {
        $hook_context["entity_bundle"] = $resource_type_param instanceof ResourceType ? $resource_type_param->getBundle() : $resource_type_param;
      }

      if ($entity_param = $params->get('entity')) {
        $hook_context["entity_id"] = $entity_param->id();
      }
    }
    $parsed['optional_filters_data'] = $config['optional_filters_data'] ?? [];
    $hook_context['cache_tags'] = $cache_tags;
    $hook_context['cache_contexts'] = $cache_contexts;
    $hook_context['resource'] = $resource;
    $hook_context['optional_data'] = [];
    $this->moduleHandler->alter('json_api_collection', $parsed, $hook_context);
    unset($parsed['optional_filters_data']);
    parse_str(http_build_query($parsed), $query_filters);
    parse_str(http_build_query($original_filters_parsed), $query_original_filters);

    $response = $this->client->serialize($resource, $query_filters);
    $exposedTerms = $this->getExposedTerms($exposed_vocabularies);
    $response['cache']['tags'] = Cache::mergeTags($response['cache']['tags'], $exposedTerms['cache_tags'], $hook_context['cache_tags']);
    $response['cache']['contexts'] = Cache::mergeContexts($response['cache']['contexts'] ?? [], $hook_context['cache_contexts']);

    $client_data = json_decode($response['data']);

    /*
     * For entityqueue, we cannot use JSON:API sorting mecanism
     * as we don't have any field attached to entities
     * where it indicate a sorting value we can use.
     * And since entity queues are limited to fewer items or we assume so.
     * we are going to alter the response and do a dynamic sorting.
     * @todo: we should only trigger if the results are less
     *   then 50 as it harcoded in JSON:API max return list
     * we don't wanna mess with the order of the rest of the pages.
     */
    if (!empty($entity_queue) && count($subqueue_items_ids) > 0) {
      $items = $client_data->data ?? [];
      $result = array_map(static fn($entity_queue_id) => current(array_values(
        array_filter($items, static fn($entity) => intval($entity->attributes->{$entity_queue_field_id}) === intval($entity_queue_id))
        )), $subqueue_items_ids);
      $result = array_filter($result, function ($e) {
        // When this value is false the element is removed.
        return $e;
      });
      $client_data->data = array_values($result);
    }

    return [
      'data' => $client_data,
      'cache' => $response['cache'],
      'filters' => $query_filters,
      'original_filters' => $query_original_filters,
      'taxonomies' => $exposedTerms['data'],
      'optional_data' => $hook_context['optional_data'],
    ];
  }

  /**
   * Get exposed terms.
   */
  protected function getExposedTerms(array $vocabularies) {
    $result = [];
    $cacheTags = [];

    $bundles = (array) $vocabularies;
    $bundles = array_filter($bundles, function ($value) {
      return $value != '0';
    });
    $bundles = array_keys($bundles);

    foreach ($bundles as $vid) {
      $terms = $this->termStorage->loadTree($vid, 0, NULL, TRUE);
      $result[$vid] = [];
      if (!empty($terms)) {
        usort($terms, function ($a, $b) {
          $weight_a = $a->get('weight')->value;
          $weight_b = $b->get('weight')->value;
          return (int) ($weight_a <=> $weight_b);
        });
      }
      foreach ($terms as $term) {
        $published = $term->get('status')->value;
        if (!$published) {
          continue;
        }
        $term = $this->entityRepository->getTranslationFromContext($term);

        $cacheTags = Cache::mergeTags($cacheTags, $term->getCacheTags());
        $term_data = [
          'id' => $term->id(),
          'uuid' => $term->uuid(),
          'slug' => $term->get("term_2_slug")->getString(),
          'label' => $term->label(),
          'results' => [],
        ];

        /*
         * Used to add/modify term data.
         *
         * How to use :
         *  hook_json_collection_exposed_term_alter($term, &$term_data).
         */
        $this->moduleHandler->alter('json_collection_exposed_term', $term, $term_data);

        if ($term->hasField('results_count')) {
          $this->injectTaxonomyResultsCount($term, $term_data, $cacheTags);
        }
        array_push($result[$vid], $term_data);
      }
    }

    return [
      "data" => $result,
      "cache_tags" => $cacheTags,
    ];
  }

  /**
   * Inject taxonomy results count.
   */
  public function injectTaxonomyResultsCount($term, &$term_data, &$cacheTags) {
    $result_count_ids = $term->get('results_count')->getValue();
    if (!empty($result_count_ids)) {
      $result_count_ids = array_map(function ($el) {
        return $el['target_id'];
      }, $result_count_ids);
      if (!empty($result_count_ids)) {
        $results_count = $this->termResultStorage->loadMultiple($result_count_ids);
        if (!empty($results_count)) {
          foreach ($results_count as $result_count) {
            $plugin = $result_count->get('plugin')->value;
            $entity_type = $result_count->get('entity_type')->value;
            $bundle = $result_count->get('bundle')->value;
            $count = $result_count->get('count')->value;
            if (!empty($plugin) && !empty($entity_type) && !empty($bundle) && !empty($count)) {
              $cacheTags = Cache::mergeTags($cacheTags, $result_count->getCacheTags());
              $term_data['results'][] = [
                'plugin' => $plugin,
                'entity_type' => $entity_type,
                'bundle' => $bundle,
                'count' => $count,
              ];
            }
          }
        }
      }
    }
  }

}
