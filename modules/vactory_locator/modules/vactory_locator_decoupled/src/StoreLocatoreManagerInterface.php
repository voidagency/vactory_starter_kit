<?php

namespace Drupal\vactory_locator_decoupled;

use Symfony\Component\HttpFoundation\Request;

interface StoreLocatoreManagerInterface {


  const ENTITY_TYPES = ['locator_entity'];
  const BUNDLES = ['vactory_locator'];
  const FIELD_NAME = 'geofield';

  public function searchGeo (string $query);

  public function v4(Request $request);

  public function getCities (Request $request);

  public function getCityName (Request $request);

}