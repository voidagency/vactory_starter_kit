<?php

namespace Drupal\vactory_reminder\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * A widget reminder interval.
 *
 * @FieldFormatter(
 *   id = "reminder_interval_formatter",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "reminder_interval"
 *   }
 * )
 */
class ReminderIntervalFormatter extends FormatterBase {

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = ['#markup' => $item->value];
    }

    return $element;
  }

}
