<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\metatag\MetatagManagerInterface;
//use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
//use Drupal\search_api\Entity\Index;

/**
 * Class SearchController
 *
 * @package Drupal\vactory_jsonapi\Controller
 */
class SearchController extends ControllerBase
{

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * SearchController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\metatag\MetatagManagerInterface $metatagManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LanguageManagerInterface $languageManager,
                              ConfigFactoryInterface $configFactory,
                              MetatagManagerInterface $metatagManager)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
    $this->metatagManager = $metatagManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Controller\ControllerBase|static
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('metatag.manager')
    );
  }


  /**
   * Output Search result.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function index(Request $request)
  {
    $search_api_fulltext = $request->query->get('q');
    $pager = $request->query->get('pager');
    $includeSummary = $request->query->get('summary');
    $limit = $request->query->get('limit');

    $limit = isset($limit) && !empty($limit) ? $limit : 10;
    if (empty($search_api_fulltext)) {
      return new JsonResponse([
        'resources' => [],
        'count' => 0,
        'metatags' => $this->getMetatag(),
        'status' => 400
      ]);
    }
    $results = $this->getIndexResults($search_api_fulltext, $pager, $includeSummary, $limit);

    return $results;
  }

  protected function getSearchMachineName()
  {
    return 'default_content_index';
  }

  /**
   * @param $searchTerm
   * @param int $pager
   *
   * @param int $includeSummary
   * @param int $limit
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getIndexResults($searchTerm, $pager = 0, $includeSummary = 0, $limit = 10)
  {
    $index = $this->entityTypeManager->getStorage('search_api_index');
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $search_api_index = $index->load($this->getSearchMachineName());
    $pager = max([0, $pager - 1]);
    $limit = $limit < 0 || $limit > 50 ? 10 : $limit;

    $query = $search_api_index->query([
      'limit' => $limit,
      'offset' => !is_null($pager) ? $pager * $limit : 0,
    ]);

    $query->keys($searchTerm);
    $query->setLanguages([$language]);
    $query->sort('search_api_relevance', 'DESC');

    $results = $query->execute();
    
    if (\Drupal::moduleHandler()->moduleExists('vactory_frequent_searches')) {
      \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
        ->updateFrequentSearches($query, $results, $search_api_index, $language);
    }

    return $this->normalizer($results->getResultItems(), $includeSummary, $results->getResultCount());
  }

  /**
   * @param $data
   *
   * @param int $includeSummary
   * @param int $count
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function normalizer($data, $includeSummary = 0, $count = 0)
  {
    $use_node_summary = $this->configFactory->get('vactory_search.settings')->get('use_node_summary');
    $front_uri = $this->configFactory->get('system.site')->get('page.front');
    $front_url = Url::fromUri('internal:' . $front_uri)->toString();
    $front_url = str_replace('/backend', '', $front_url);

//    Final results will be available in this array.
    $formated_results = [];

    $response = array_map(function ($element) use (&$formated_results, $front_url, $includeSummary, $use_node_summary) {
      $needed = ['url', 'title', 'type'];

      if ($use_node_summary) {
        $needed = ['url', 'title', 'type', 'node_summary'];
      }

      if (intval($includeSummary) == '1') {
        array_push($needed, 'node_summary');
      }
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

      $formated_results[] = $flatData;
      return $flatData;
    }, $data);

    if ($use_node_summary) {
      foreach ($formated_results as &$item) {
        $item['excerpt'] = $item['node_summary'];
        unset($item['node_summary']);
      }
    }

    return new JsonResponse([
      'resources' => $formated_results,
      'count' => $count,
//      'metatags' => $this->getMetatag(),
      'status' => 200
    ]);
  }

  /**
   * @return false|string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMetatag()
  {
    $entity = $this->entityTypeManager->getStorage('view')->load('vactory_search');
    $metatags = metatag_get_default_tags($entity);

    $tags = $this->metatagManager->generateRawElements($metatags, $entity);

    return json_encode($tags);
  }
}
