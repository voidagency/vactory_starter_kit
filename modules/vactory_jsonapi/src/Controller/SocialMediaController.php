<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Social network API Endpoint.
 */
class SocialMediaController extends ControllerBase {

  /**
   * Get available given field social network platforms.
   */
  public function getSocialMedia() {
    $params = \Drupal::request()->query->all();
    $available_platforms = \Drupal::service('plugin.manager.social_media_links.platform')->getPlatforms();
    if (isset($params['entity_type']) && isset($params['bundle'])  && isset($params['field_name'])) {
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
            $field_social_media_settings = $bundle_fields[$field_name]->getSettings();
            $enabled_platforms = array_filter($field_social_media_settings['platforms'], function ($plateform) {
              return $plateform['enabled'];
            });
            $enabled_platforms = array_keys($enabled_platforms);
            $available_platforms = array_filter($available_platforms, function ($key) use($enabled_platforms) {
              return in_array($key, $enabled_platforms);
            }, ARRAY_FILTER_USE_KEY);
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

    $available_platforms = array_map(function ($platform) {
      unset(
        $platform['class'],
        $platform['instance'],
        $platform['provider']
      );
      return $platform;
    }, $available_platforms);
    $data = [
      'data' => array_values($available_platforms),
    ];
    return new JsonResponse($data, 200);
  }

}
