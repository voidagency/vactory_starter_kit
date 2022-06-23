<?php

namespace Drupal\vactory_decoupled;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;

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
    $resource = $config['resource'];
    $filters = $config['filters'];
    $exposed_vocabularies = $config['vocabularies'];
    $entity_queue = $config['entity_queue'] ?? '';
    $entity_queue_field_id = $config['entity_queue_field_id'] ?? '';

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
      $parsed[trim($name)] = urldecode(trim($qsvalue));
    }
 
    parse_str(http_build_query($parsed), $query_filters);

    $response = $this->client->serialize($resource, $query_filters);

    return [
      'data' => json_decode($response),
      'filters' => $query_filters,
      'taxonomies' => $this->getExposedTerms($exposed_vocabularies),
    ];
  }

  protected function getExposedTerms(array $vocabularies) {
    $result = [];

    $entityTypeManager = \Drupal::service('entity_type.manager');
    $taxonomyTermStorage = $entityTypeManager->getStorage('taxonomy_term');
    $slugManager = \Drupal::service('vactory_core.slug_manager');
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
        array_push($result[$vid], [
          'id' => $term->id(),
          'uuid' => $term->uuid(),
          'slug' => $slugManager->taxonomy2Slug($term),
          'label' => $term->label(),
        ]);
      }

    }

    return $result;
  }

}
