<?php

namespace Drupal\vactory_google_places\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide Google places form element.
 *
 * @FormElement("vactory_google_places")
 */
class VactoryGooglePlaces extends FormElement {

  /**
   * {@inheritDoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [
        [self::class, 'processGooglePlaces'],
      ],
      '#element_validate' => [
        [self::class, 'validateGooglePlaces'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Google place form element process callback.
   */
  public static function processGooglePlaces(&$element, FormStateInterface $form_state, &$form) {
    $api_key = \Drupal::service('state')->get('google_places_api_key');
    $countries = \Drupal::config('vactory_google_places.settings')->get('countries');
    $countries = array_values(array_map('strtolower', $countries ?? []));
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $default_value = isset($element['#default_value']) ? $element['#default_value'] : NULL;
    $element['google_places'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $element['google_places']['place'] = [
      '#type' => 'textfield',
      '#title' => isset($element['#title']) ? $element['#title'] : '',
      '#size' => 64,
      '#default_value' => $default_value && isset($default_value['place']) ? $default_value['place'] : NULL,
      '#attributes' => ['class' => ['vactory-google-places']],
    ];
    if (isset($element['#placeholder'])) {
      $element['google_places']['place']['#placeholder'] = $element['#placeholder'];
    }
    $element['google_places']['latitude'] = [
      '#type' => 'hidden',
    ];
    $element['google_places']['longitude'] = [
      '#type' => 'hidden',
    ];
    $google_map_key = [
      '#tag' => 'script',
      '#attributes' => ['src' => '//maps.googleapis.com/maps/api/js?key=' . $api_key . '&sensor=true&libraries=places&language=' . $langcode],
    ];
    $element['#attached']['html_head'][] = [$google_map_key, 'googleMapKey'];
    $element['#attached']['drupalSettings']['place']['autocomplete'] = $countries;
    $element['#attached']['library'][] = 'vactory_google_places/autocomplete';

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Call from form API case.
    $place = isset($input['place']) && !empty($input['place']) ? $input['place'] : '';
    // Call from Field API case.
    $place = empty($place) && isset($input['google_places']['place']) && !empty($input['google_places']['place']) ? $input['google_places']['place'] : '';
    if (!empty($place)) {
      $geo_infos = \Drupal::service('vactory_google_places.manager')->geoLatLong($place);
      if (isset($input['place'])) {
        // Call from form API case.
        $input['latitude'] = $geo_infos ? $geo_infos['latitude'] : '';
        $input['longitude'] = $geo_infos ? $geo_infos['longitude'] : '';
      }
      if (isset($input['google_places']['place'])) {
        // Call from Field API case.
        $input['google_places']['latitude'] = $geo_infos ? $geo_infos['latitude'] : '';
        $input['google_places']['longitude'] = $geo_infos ? $geo_infos['longitude'] : '';
      }
      return $input;
    }

    return NULL;
  }

  /**
   * Google places form element validate callback.
   */
  public static function validateGooglePlaces(&$element, FormStateInterface $form_state, &$form) {
  }

}
