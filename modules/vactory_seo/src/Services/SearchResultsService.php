<?php

namespace Drupal\vactory_seo\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;

/**
 * Service for handling search index results.
 */
class SearchResultsService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new SearchResultsService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
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
