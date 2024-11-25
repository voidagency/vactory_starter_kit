<?php

namespace Drupal\vactory_remote_media_transcription\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'truncated_text' formatter.
 *
 * @FieldFormatter(
 *   id = "truncated_text",
 *   label = @Translation("Truncated text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class TruncatedTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // Limit the text to 50 characters.
      $text = mb_substr($item->value, 0, 50);
      if (mb_strlen($item->value) > 50) {
        // Add ellipsis if the text is truncated.
        $text .= '...'; 
      }

      $elements[$delta] = [
        '#markup' => $text,
      ];
    }

    return $elements;
  }

}
