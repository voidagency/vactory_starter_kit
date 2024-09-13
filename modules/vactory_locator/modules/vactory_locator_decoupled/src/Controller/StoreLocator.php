<?php

namespace Drupal\vactory_locator_decoupled\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\vactory_locator_decoupled\StoreLocatoreManagerInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class StoreLocator controller.
 */
class StoreLocator extends ControllerBase {

  /**
   * The Store locator service.
   *
   * @var \Drupal\vactory_locator_decoupled\StoreLocatoreManagerInterface
   */
  protected $manager;

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
   * Matetags manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;


  /**
   * Rendrer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * StoreLocator constructor.
   */
  public function __construct(
    StoreLocatoreManagerInterface $store_locator_manager,
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    ConfigFactoryInterface $configFactory,
    MetatagManagerInterface $metatagManager,
    RendererInterface $renderer
  ) {
    $this->manager = $store_locator_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
    $this->metatagManager = $metatagManager;
    $this->renderer = $renderer;
  }

  /**
   * Create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_locator_decoupled.store_manager'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('metatag.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Perform Store Locator actions.
   */
  public function index(Request $request) {
    $locality = $request->query->get('locality');
    $category = $request->query->get('category');
    $pager = $request->query->get('pager') ?? 0;
    $limit = $request->query->get('limit') ?? 10;

    $pager = max([0, $pager - 1]);
    $limit = $limit < 0 || $limit > 50 ? 10 : $limit;
    try {
      return $this->handleRequest($locality, $category, $pager, $limit);
    }
    catch (\Exception $e) {
      return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Handle Controller request and prepare the view.
   */
  protected function handleRequest($locality, $category, $pager = 0, $limit = 10) {
    $view = $this->entityTypeManager->getStorage('view')
      ->load('vactory_locator')->getExecutable();
    $view->setDisplay('store_locator_display');
    $view->initDisplay();
    $view->preExecute();

    /*
     * It can either be calculated via offset or current page but no need to
     * use it since the view is already handling the limit.
     */
    $view->setItemsPerPage($limit);

    $view->setCurrentPage($pager);

    if (isset($locality)) {
      $lon_lat = $this->manager->searchGeo($locality) ?? '';
      if (isset($lon_lat) && !empty($lon_lat)) {
        /* This snipet is for the sort only option */
        if (isset($view->sort['field_vactory_locator_geofield_proximity'])) {
          $view->sort['field_vactory_locator_geofield_proximity']->options['source_configuration']['origin'] = [
            'lat' => (string) $lon_lat['result']['lat'],
            'lon' => (string) $lon_lat['result']['lng'],
          ];
        }
      }
    }

    if (isset($category)) {
      $view->setArguments(['field_locator_category_target_id' => $category]);
    }

    $view->get_total_rows = TRUE;

    return $this->normalizer($view);
  }

  /**
   * Executes and render json result with cache Metadata.
   */
  protected function normalizer(ViewExecutable $view) {
    $resultSet = [];

    $view_render_array = [];
    $rendered_view = NULL;
    $cache['#cache'] = [
      'max-age' => Cache::PERMANENT,
      'contexts' => [
        'url.query_args',
      ],
    ];

    $this->renderer->executeInRenderContext(new RenderContext(), function () use ($view, &$view_render_array, &$rendered_view) {
      $view_render_array = $view->render($view->current_display);
      $rendered_view = $view_render_array['#markup']->jsonSerialize();
    });

    $result = $rendered_view;
    $resultSet['resources'] = json_decode($result, TRUE);
    $resultSet['count'] = $view->total_rows;

    /*
     * In case metatags are filter dependant
     * $resultSet['metatags'] = json_decode($this->getMetatag($view), TRUE);
     */
    $response = new CacheableJsonResponse($resultSet, Response::HTTP_OK);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($view_render_array));
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($cache));

    return $response;
  }

  /**
   * Get Metatag.
   */
  public function getMetatag(ViewExecutable $view) {
    $metatags = metatag_get_view_tags($view, $view->current_display);
    $tags = $this->metatagManager->generateRawElements($metatags, $view->storage);
    return json_encode($tags);
  }

  /**
   * Places autocomplete.
   */
  public function placesAutocomplete(Request $request) {
    return $this->manager->getCities($request);
  }

  /**
   * Cities Name.
   */
  public function citiesName(Request $request) {
    return $this->manager->getCityName($request);
  }

  /**
   * Defines an api to handle grouping of entities.
   *
   * For now its handled via a view on cities can be upgraded
   * to handle multiple groupings.
   */
  public function getGrouping(Request $request) {
    $locality = $request->query->get('city');
    try {
      $view = $this->entityTypeManager->getStorage('view')
        ->load('vactory_locator_cities')->getExecutable();
      $view->setDisplay('locator_cities_api');

      $view->initDisplay();
      $view->preExecute();

      if (isset($locality)) {
        $lon_lat = $this->manager->searchGeo($locality) ?? '';
        if (isset($lon_lat) && !empty($lon_lat)) {
          /* This snipet is for the sort only option */
          if (isset($view->sort['field_geofield_city_proximity'])) {
            $view->sort['field_geofield_city_proximity']->options['source_configuration']['origin'] = [
              'lat' => (string) $lon_lat['result']['lat'],
              'lon' => (string) $lon_lat['result']['lng'],
            ];
          }
        }
      }
      return $this->normalizer($view);
    }
    catch (\Exception $e) {
      return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
