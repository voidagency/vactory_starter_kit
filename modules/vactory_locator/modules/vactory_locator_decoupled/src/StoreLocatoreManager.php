<?php

namespace Drupal\vactory_locator_decoupled;


use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class StoreLocatoreManager implements StoreLocatoreManagerInterface {
  

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * Config Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \GuzzleHttp\Client
   */
  private Client $client;

  /**
   * @var string
   */
  private string $language;

  const CID = 'cities_json';

  /**
   * MapManager constructor.
   */
  public function __construct()
  {
    $this->configFactory = \Drupal::service('config.factory');
    $this->messenger = \Drupal::service('messenger');
    $this->client = \Drupal::httpClient();
    $this->language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  }


  /**
   * GetSession id to optimise google request (pricewise).
   * @link https://developers.google.com/maps/documentation/places/web-service/autocomplete
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return string
   */
  public function v4(Request $request) {
    return $request->getSession()->getId();
  }


  /**
   * To use in case you want to get lon/lat
   * from Google (tooo pricey).
   * @param $place_id
   * @return array
   */
  // public function getDetails (string $place_id)
  // {
  //   if (!isset($place_id) || empty($place_id)) return [];
  //   $client = \Drupal::httpClient();
  //   $res = $client->get('https://maps.googleapis.com/maps/api/place/details/json', [
  //     'query' => [
  //       'place_id' => $place_id,
  //       'language' => "fr",
  //       'key' => getenv('GOOGLE_PLACES_API'),
  //       'fields' => 'name,geometry'
  //     ],
  //   ]);
  //   $response = json_decode($res->getBody(), TRUE);

  //   return [
  //     'result' => $response['result']['geometry']['location'],
  //     'name' => $response['result']['name']
  //   ];
  // }


  /**
   * Gets lon/lat from opencage Webservice
   * (Kinda cheap might also replace it with
   * any other Geo service like
   * positionStack).
   * @param string $query
   *
   * @return array
   */
  public function searchGeo (string $query)
  {
    if (empty($query)) return [];
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
          'output' => 'json'
        ],
        'headers' => [
          'cache-control' => 'max-age=86400,public',
        ]
      ]);
      $response = json_decode($res->getBody(), TRUE);
      //$town = explode(",", $response['results'][0]['formatted'])[0];
      return [
        'result' => [
          'lat' => $response['results'][0]['geometry']['lat'],
          'lng' => $response['results'][0]['geometry']['lng']
        ],
        'name' => (isset($response['results'][0]['components']['city'])) ? ($response['results'][0]['components']['city']) : ($response['results'][0]['components']['country']) ,
      ];
    } catch (\Exception $e) {
      $this->messenger->addError(t('Requête invalide!'));
      return [];
    }
  }


  /**
   * Callback for `google places autocomplete API method.
   * @param Request $request
   * @return JsonResponse
   */

  public function getCities (Request $request) {
    $client = $this->client;
    $query = $request->query->get('city');
    if (strlen(trim($query)) < 3)  return new JsonResponse([], 200);
    try {
      $res = $client->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
        'query' => [
          'input' => Xss::filter($request->query->get('city')),
          'types' => '(regions)',
          'language' => "fr",
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
        ]
      ];
      if ($response['status'] !== 'OK') {
        $this->messenger->addError(t('Requête invalide!'));
        return new JsonResponse(['message' => t('Invalid Request!')], 500);
      }
      $results = array_map(static fn($arr) => ['content' => $arr['description'], 'value' => $arr['place_id']], $response['predictions']);

      $response = CacheableJsonResponse::create($results, $res->getStatusCode());
      $response->getCacheableMetadata()->addCacheableDependency(CacheableMetadata::createFromRenderArray($cache));
      return $response;
    } catch (\Exception $e) {

      $this->messenger->addError(t('Requête invalide!'));
      return new JsonResponse(['message' => t('Exception thrown')], 500);

    }
  }

}