<?php

namespace Drupal\vactory_locator_decoupled;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StoreLocatoreManager.
 *
 * Provides various methods for managing store locator functionality.
 */
class StoreLocatoreManager implements StoreLocatoreManagerInterface {

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * HTTP client service.
   *
   * @var \GuzzleHttp\Client
   */
  private Client $client;

  /**
   * Current language.
   *
   * @var string
   */
  private string $language;

  /**
   * Cache ID for storing cities' data.
   */
  const CID = 'cities_json';

  /**
   * StoreLocatoreManager constructor.
   *
   * Initializes services used by the store locator manager.
   */
  public function __construct() {
    $this->configFactory = \Drupal::service('config.factory');
    $this->messenger = \Drupal::service('messenger');
    $this->client = \Drupal::httpClient();
    $this->language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * Retrieves the session ID for Google Places requests.
   *
   * @link https://developers.google.com/maps/documentation/places/web-service/autocomplete
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string
   *   The session ID.
   */
  public function v4(Request $request) {
    return $request->getSession()->getId();
  }

  /**
   * Performs a search query to retrieve geolocation data from OpenCage.
   *
   * @param string $query
   *   The query string containing the location to search for.
   *
   * @return array
   *   The search results, including latitude and longitude.
   */
  public function searchGeo(string $query) {
    if (empty($query)) {
      return [];
    }

    $client = $this->client;

    try {
      $res = $client->get('https://api.opencagedata.com/geocode/v1/json', [
        'query' => [
          'q' => $query,
          'language' => $this->language,
          'key' => getenv('OPEN_CAGE_API'),
          'no_annotations' => 1,
          'country' => 'MA,FR',
          'fields' => 'label,latitude,longitude,name',
          'output' => 'json',
        ],
        'headers' => [
          'cache-control' => 'max-age=86400,public',
        ],
      ]);

      $response = json_decode($res->getBody(), TRUE);

      return [
        'result' => [
          'lat' => $response['results'][0]['geometry']['lat'],
          'lng' => $response['results'][0]['geometry']['lng'],
        ],
        'name' => $response['results'][0]['components']['city'] ?? $response['results'][0]['components']['country'],
      ];
    }
    catch (\Exception $e) {
      $this->messenger->addError(t('Requête invalide!'));
      \Drupal::logger('vactory_locator')->warning(t('Requête invalide! Geolocalisation not found.'));
      return new JsonResponse([], 200);
    }
  }

  /**
   * Retrieves a list of cities using Google Places Autocomplete API.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request containing the search query.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the city data.
   */
  public function getCities(Request $request) {
    $client = $this->client;
    $query = $request->query->get('city');

    if (strlen(trim($query)) < 3) {
      return new JsonResponse([], 200);
    }

    try {
      $res = $client->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
        'query' => [
          'input' => Xss::filter($query),
          'types' => '(regions)',
          'language' => 'fr',
          'key' => getenv('GOOGLE_PLACES_API'),
          'sessiontoken' => $this->v4($request),
          'components' => 'country:ma',
        ],
      ]);

      $response = json_decode($res->getBody(), TRUE);
      $cache['#cache'] = [
        'max-age' => Cache::PERMANENT,
        'contexts' => [
          'url.query_args:city',
        ],
        'tags' => [
          self::CID . ':' . $query,
        ],
      ];

      if ($response['status'] !== 'OK') {
        $this->messenger->addError(t('Requête invalide!'));
        return new JsonResponse(['message' => t('Invalid Request!')], 500);
      }

      $results = array_map(static fn($arr) => [
        'content' => $arr['description'],
        'value' => $arr['place_id'],
      ], $response['predictions']);

      $cacheableResponse = new CacheableJsonResponse($results, $res->getStatusCode());
      $cacheableResponse->getCacheableMetadata()->addCacheableDependency(CacheableMetadata::createFromRenderArray($cache));

      return $cacheableResponse;
    }
    catch (\Exception $e) {
      $this->messenger->addError(t('Requête invalide!'));
      \Drupal::logger('vactory_locator')->warning(t('GOOGLE PLACES API: Requête invalide! City not found.'));
      return new JsonResponse(['message' => t('GOOGLE_PLACES_API: City not found.')], 200);
    }
  }

  /**
   * Retrieves the city name using OpenCage API based on the provided query.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request containing the search query.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the city name.
   */
  public function getCityName(Request $request) {
    $query = $request->query->get('q');

    if (empty($query)) {
      return new JsonResponse([], 200);
    }

    try {
      $client = $this->client;
      $res = $client->get('https://api.opencagedata.com/geocode/v1/json', [
        'query' => [
          'q' => $query,
          'language' => $this->language,
          'key' => getenv('OPEN_CAGE_API'),
          'no_annotations' => 1,
          'country' => 'MA,FR',
          'output' => 'json',
        ],
        'headers' => [
          'cache-control' => 'max-age=86400,public',
        ],
      ]);

      $response = json_decode($res->getBody(), TRUE);
      $address = $response['results'][0]['components']['road'] . ' ' .
        $response['results'][0]['components']['city'] . ' ' .
        $response['results'][0]['components']['postcode'] . ' ' .
        $response['results'][0]['components']['country'];

      return new JsonResponse(['result' => $address], 200);
    }
    catch (\Exception $e) {
      $this->messenger->addError(t('Requête invalide!'));
      \Drupal::logger('vactory_locator')->warning(t('OPEN CAGE API: Requête invalide! City name not found.'));
      return new JsonResponse(['message' => t('Exception thrown: City name not found.')], 200);
    }
  }

}
