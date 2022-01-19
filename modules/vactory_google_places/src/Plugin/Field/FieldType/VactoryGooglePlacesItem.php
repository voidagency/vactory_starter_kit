<?php

namespace Drupal\vactory_google_places\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'vactory_google_places' field type.
 *
 * @FieldType(
 *   id = "vactory_google_places",
 *   label = @Translation("Google Places"),
 *   category = @Translation("General"),
 *   default_widget = "vactory_google_places_autocomplete",
 *   default_formatter = "vactory_google_places_formatter"
 * )
 */
class VactoryGooglePlacesItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('place')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['place'] = DataDefinition::create('string');
    $properties['longitude'] = DataDefinition::create('string');
    $properties['latitude'] = DataDefinition::create('string');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [
      'place' => [
        'type' => 'text',
        'not null' => TRUE,
        'description' => 'Google place name',
      ],
      'longitude' => [
        'type' => 'text',
        'not null' => FALSE,
        'description' => 'Google place longitude',
      ],
      'latitude' => [
        'type' => 'text',
        'not null' => FALSE,
        'description' => 'Google place latitude',
      ],
    ];

    $schema = [
      'columns' => $columns,
    ];

    return $schema;
  }

}
