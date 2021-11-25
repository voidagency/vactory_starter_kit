<?php

namespace Drupal\vactory_google_places\Services;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\State;
use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\Json;

/**
 * Defines the VactoryGooglePlacesManager service, for return parse GeoJson.
 */
class VactoryGooglePlacesManager {

  /**
   * Drupal http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Language Manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Service constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ClientInterface $http_client, LanguageManagerInterface $language_manager, State $state) {
    $this->httpClient = $http_client;
    $this->languageManager = $language_manager;
    $this->state = $state;
  }

  /**
   * Return json list of geolocation matching $text.
   *
   * @param string $address
   *   The address query for search a place.
   *
   * @return array
   *   An array of matching location.
   */
  public function geoLatLong($address) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    // Get google places API key from module settings.
    $api_key = $this->state->get('google_places_api_key') ?? '';
    $geocodes = NULL;

    $language = isset($langcode) ? $langcode : $default_langcode;

    $query = [
      'key' => $api_key,
      'address' => $address,
      'language' => $language,
      'sensor' => 'false',
    ];
    $uri = 'https://maps.googleapis.com/maps/api/geocode/json';

    $response = $this->httpClient->request('GET', $uri, [
      'query' => $query,
    ]);

    if (empty($response->error)) {
      $data = Json::decode($response->getBody());

      if (strtoupper($data['status']) == 'OK') {
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];
        $geocodes = ['latitude' => $lat, 'longitude' => $lng];
      }
    }
    return $geocodes;
  }

}
