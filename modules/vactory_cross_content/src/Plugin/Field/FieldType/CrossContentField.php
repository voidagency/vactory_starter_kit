<?php

namespace Drupal\vactory_cross_content\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_cross_content' field type.
 *
 * @FieldType(
 *   id = "field_cross_content",
 *   label = @Translation("Cross Content"),
 *   module = "vactory_cross_content",
 *   default_widget = "field_cross_content_widget",
 *   default_formatter = "field_cross_content_formatter",
 * )
 */
class CrossContentField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type'     => 'text',
          'size'     => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Related Nodes'));
    return $properties;
  }

}
