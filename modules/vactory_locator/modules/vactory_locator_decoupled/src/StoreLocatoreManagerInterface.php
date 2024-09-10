<?php

namespace Drupal\vactory_locator_decoupled;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface StoreLocatoreManagerInterface.
 *
 * Defines the contract for the Store Locator Manager service.
 */
interface StoreLocatoreManagerInterface {

  /**
   * Entity types that the locator manages.
   *
   * @var string[]
   */
  const ENTITY_TYPES = ['locator_entity'];

  /**
   * Bundles that the locator manages.
   *
   * @var string[]
   */
  const BUNDLES = ['vactory_locator'];

  /**
   * The name of the field storing geolocation data.
   *
   * @var string
   */
  const FIELD_NAME = 'geofield';

  /**
   * Search for entities using a geo query.
   */
  public function searchGeo(string $query);

  /**
   * Version 4.
   */
  public function v4(Request $request);

  /**
   * Retrieve a list of cities.
   */
  public function getCities(Request $request);

  /**
   * Get the name of a city.
   */
  public function getCityName(Request $request);

}
