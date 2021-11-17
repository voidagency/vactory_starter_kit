<?php

namespace Drupal\vactory_sondage\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Sondage option field type definition.
 *
 * @FieldType(
 *   id="vactory_sondage_option",
 *   label=@Translation("Sondage Option"),
 *   default_formatter="vactory_sondage_option_formatter",
 *   default_widget="vactory_sondage_option_widget"
 * )
 */
class SondageOptionItem extends FieldItemBase {

  /**
   * {@inheritDoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['option_value'] = DataDefinition::create('string');
    $properties['option_text'] = DataDefinition::create('string');
    $properties['option_image'] = DataDefinition::create('string');
    return $properties;
  }

  /**
   * {@inheritDoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'option_value' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ],
        'option_text' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'option_image' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $option_value = $this->get('option_value')->getValue();
    $option_text = $this->get('option_text')->getValue();
    $option_image = $this->get('option_image')->getValue();
    return empty($option_value) || (empty($option_text) && empty($option_image));
  }

}
