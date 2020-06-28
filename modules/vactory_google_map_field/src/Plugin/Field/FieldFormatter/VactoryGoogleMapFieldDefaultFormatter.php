<?php

namespace Drupal\vactory_google_map_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'vactory_google_map_field' formatter.
 *
 * @FieldFormatter(
 *   id = "vactory_google_map_field_default",
 *   label = @Translation("Vactory Google Map Field default"),
 *   field_types = {
 *     "vactory_google_map_field"
 *   }
 * )
 */
class VactoryGoogleMapFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
/*    foreach ($items as $delta => $item) {
      $element = [
        '#theme' => 'vactory_google_map_field',
//        '#name' => $item->name,
        '#lat' => $item->lat,
        '#lon' => $item->lon,
        '#zoom' => $item->zoom,
        '#type' => $item->type,*/
//        '#show_marker' => $item->marker === "1" ? "true" : "false",
//        '#show_controls' => $item->controls === "1" ? "true" : "false",
//        '#width' => $item->width ? $item->width : '320px',
//        '#height' => $item->height ? $item->height : '200px',
 //     ];

      // Handle markup for InfoWindow popup.
      /*if (!empty($item->infowindow)) {
        $element['#infowindow'] = [
          '#markup' => $item->infowindow,
          '#allowed_tags' => $this->allowedTags(),
        ];
      }*/

   /*   $element['#attached']['library'][] = 'vactory_google_map_field/google-map-field-renderer';
      $element['#attached']['library'][] = 'vactory_google_map_field/google-map-apis';

      $elements[$delta] = $element;
    }*/

    return $elements;
  }

  /**
   * Retrieves list of allowed tags for infowindow popup.
   *
   * @return array
   *   List of allowed tags to use on infowindow popup.
   */
  private function allowedTags() {
    return [
      'div',
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'p', 'span', 'br', 'em', 'strong',
      'a', 'img', 'video',
      'ul', 'ol', 'li',
    ];
  }

}
