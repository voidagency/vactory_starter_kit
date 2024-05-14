<?php

namespace Drupal\vactory_seo\Plugin\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\Url;

/**
 * Sea search result per node.
 */
class SeoSearchResultFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cacheability.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected ?CacheableMetadata $cacheMetadata = NULL;

  /**
   * Create instance.
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->languageManager = $container->get('language_manager');
    $instance->configFactory = $container->get('config.factory');
    $instance->cacheMetadata = new CacheableMetadata();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {

    $entity = $this->getEntity();
    if ($entity->getEntityTypeId() !== 'node' || $entity->bundle() !== 'vactory_seo') {
      return;
    }
    $title = $entity->get('title')->value;
    $res = $this->getIndexResults($title);

    $this->list[0] = $this->createItem(0, $res);
  }

  /**
   * Get search index results.
   */
  public function getIndexResults($searchTerm) {
    $index = $this->entityTypeManager->getStorage('search_api_index');
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $search_api_index = $index->load('default_content_index');

    $query = $search_api_index->query();

    $query->keys($searchTerm);
    $query->setLanguages([$language]);
    $query->sort('search_api_relevance', 'DESC');

    $results = $query->execute();

    if (\Drupal::moduleHandler()->moduleExists('vactory_frequent_searches')) {
      \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
        ->updateFrequentSearches($query, $results, $search_api_index, $language);
    }

    return $this->normalizer($results->getResultItems());
  }

  /**
   * Normalize search result.
   */
  public function normalizer($data) {
    $use_node_summary = $this->configFactory->get('vactory_search.settings')->get('use_node_summary');
    $front_uri = $this->configFactory->get('system.site')->get('page.front');
    $front_url = Url::fromUri('internal:' . $front_uri)->toString();
    $front_url = str_replace('/backend', '', $front_url);

    // Final results will be available in this array.
    $formated_results = [];

    $response = array_map(function ($element) use (&$formated_results, $front_url, $use_node_summary) {
      $needed = ['url', 'title', 'type'];

      if ($use_node_summary) {
        $needed = ['url', 'title', 'type', 'node_summary'];
      }

      array_push($needed, 'node_summary');
      $flatData = [];
      foreach ($element->getFields(TRUE) as $key => $item) {
        if (in_array($key, $needed)) {
          $rawValues = $item->getValues();
          $rawValues = reset($rawValues);
          $rawValues = str_replace('/backend', '', $rawValues);

          if ($key === 'url' && strval($rawValues) === $front_url) {
            $flatData[$key] = '/' . $this->languageManager->getCurrentLanguage()->getId();
            continue;
          }
          $flatData[$key] = strval($rawValues);
        }
      }
      $flatData['excerpt'] = $element->getExcerpt();
      if (!$flatData['excerpt']) {
        $flatData['excerpt'] = $flatData['node_summary'];
      }

      $formated_results[] = $flatData;
      return $flatData;
    }, $data);

    if ($use_node_summary) {
      foreach ($formated_results as &$item) {
        $item['excerpt'] = $item['node_summary'];
        unset($item['node_summary']);
      }
    }
    return $formated_results;
  }

}
