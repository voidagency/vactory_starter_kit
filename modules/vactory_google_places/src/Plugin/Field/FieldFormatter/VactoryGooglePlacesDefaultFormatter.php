<?php

namespace Drupal\vactory_google_places\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Google Places Default' formatter.
 *
 * @FieldFormatter(
 *   id = "vactory_google_places_formatter",
 *   label = @Translation("Google Places Default"),
 *   field_types = {
 *     "vactory_google_places"
 *   }
 * )
 */
class VactoryGooglePlacesDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'vactory_google_places',
        '#content' => $item->getValue(),
      ];
    }

    return $element;
  }

}
