<?php

namespace Drupal\vactory_push_notification;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the subscription entity schema handler.
 */
class SubscriptionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    switch ($field_name) {
      case 'token':
        $schema['fields'][$field_name]['not null'] = TRUE;
        $this->addSharedTableFieldUniqueKey($storage_definition, $schema);
        break;
    }

    return $schema;
  }

}
