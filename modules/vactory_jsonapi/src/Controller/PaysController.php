<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pays API Endpoint.
 */
class PaysController extends ControllerBase {

  /**
   * Get available given field pays.
   */
  public function getPays() {
    $params = \Drupal::request()->query->all();
    $available_pays = \Drupal::getContainer()->get('address.country_repository')->getList();
    if (isset($params['entity_type']) && isset($params['bundle']) && isset($params['field_name'])) {
      $entity_type = $params['entity_type'];
      $bundle = $params['bundle'];
      $field_name = $params['field_name'];
      $is_valid_entity_type = \Drupal::entityTypeManager()->hasDefinition($entity_type);
      $errors = [];
      if ($is_valid_entity_type) {
        $available_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
        if (isset($available_bundles[$bundle])) {
          $bundle_fields = \Drupal::getContainer()->get('entity_field.manager')
            ->getFieldDefinitions($entity_type, $bundle);
          if (isset($bundle_fields[$field_name])) {
            $field_pays_settings = $bundle_fields[$field_name]->getSettings();
            if (!empty($field_pays_settings['available_countries'])) {
              $countries_from_settings = $field_pays_settings['available_countries'];
              $available_pays = array_filter($available_pays, function ($country_code) use($countries_from_settings) {
                return in_array($country_code, $countries_from_settings);
              }, ARRAY_FILTER_USE_KEY);
            }
          }
          else {
            $errors['errors'] = 'No field "' . $field_name . '" has been founded';
          }
        }
        else {
          $errors['errors'] = 'No bundle "' . $bundle . '" has been founded';
        }
      }
      else {
        $errors['errors'] = 'No entity type "' . $entity_type . '" has been founded';
      }
    }
    if (!empty($errors)) {
      return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    array_walk($available_pays, function (&$el, $key) {
      $el = [
        "id" => $key,
        "name" => $el,
      ];
    });
    $data = [
      'data' => array_values($available_pays),
    ];
    return new JsonResponse($data, 200);
  }

}
