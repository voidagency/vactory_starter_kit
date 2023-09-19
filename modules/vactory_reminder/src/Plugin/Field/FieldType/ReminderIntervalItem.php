<?php

namespace Drupal\vactory_reminder\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of reminder interval.
 *
 * @FieldType(
 *   id = "reminder_interval",
 *   label = @Translation("Reminder Interval"),
 *   default_formatter = "reminder_interval_formatter",
 *   default_widget = "reminder_interval_widget",
 * )
 */
class ReminderIntervalItem extends FieldItemBase {

  /**
   * {@inheritDoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['day'] = DataDefinition::create('string')
      ->setLabel(t('Value'));
    $properties['hour'] = DataDefinition::create('string')
      ->setLabel(t('Hour'));
    $properties['minute'] = DataDefinition::create('string')
      ->setLabel(t('Minute'));
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Value'));
    return $properties;
  }

  /**
   * {@inheritDoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'day' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'hour' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'minute' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'value' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $day = $this->get('day')->getValue();
    $hour = $this->get('hour')->getValue();
    $minute = $this->get('minute')->getValue();
    return empty($day) && empty($hour) && empty($minute);
  }

  /**
   * {@inheritDoc}
   */
  public function setValue($values, $notify = TRUE) {
    $day = !empty($values['day']) ? $values['day'] : NULL;
    $hour = !empty($values['hour']) ? $values['hour'] : NULL;
    $minute = !empty($values['minute']) ? $values['minute'] : NULL;
    $value = $day ? "$day day " : '';
    $value .= $hour ? "$hour hour " : '';
    $value .= $minute ? "$minute minute" : '';
    $values['value'] = trim($value);
    parent::setValue($values, $notify);
  }

}
