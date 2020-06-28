<?php

namespace Drupal\vactory_cross_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'field_cross_content' formatter.
 *
 * @FieldFormatter(
 *   id = "field_cross_content_formatter",
 *   module = "vactory_cross_content",
 *   label = @Translation("Cross Content"),
 *   field_types = {
 *     "field_cross_content"
 *   }
 * )
 */
class CrossContentFormatter extends FormatterBase {

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      $elements[$delta] = [
        '#entity_delta' => $delta,
        '#item'         => $item,
        '#content'      => $item->value,
      ];
    }

    return $elements;
  }

}
