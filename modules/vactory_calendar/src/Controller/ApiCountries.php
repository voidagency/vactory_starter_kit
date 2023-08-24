<?php

namespace Drupal\vactory_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Api Countries.
 */
class ApiCountries extends ControllerBase {

  /**
   * Fetch the list of countries.
   */
  public function getCountries() {
    $country_repository = \Drupal::service('address.country_repository');
    // $countries = "fahd";
    $countries = $country_repository->getList();

    return new JsonResponse($countries);
  }

}
