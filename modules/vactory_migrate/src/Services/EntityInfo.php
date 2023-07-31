<?php

namespace Drupal\vactory_migrate\Services;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Get entities info.
 */
class EntityInfo {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }


  public function getDestinationByMigrationId($migration_id) {
    $migration_config = $this->configFactory->get('migrate_plus.migration.' . $migration_id);
    $destination = $migration_config->get('destination');
    if (!isset($destination)){
     return [];
    }
    $plugin = $destination['plugin'];
    $entity = explode(':', $plugin)[1];
    return [
      'entity' => $entity,
      'bundle' => array_key_exists('default_bundle', $destination) ? $destination['default_bundle'] : $entity,
    ];
  }


  function getRelatedTablesByEntityAndBundle($entity_type_id, $bundle) {
    $baseTable = $this->entityTypeManager->getStorage($entity_type_id)
      ->getBaseTable();
    $dataTable = $this->entityTypeManager->getStorage($entity_type_id)
      ->getDataTable();
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);
    $id = $entity_type_definition->getKey('id');
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    $tables = [];
    foreach ($field_definitions as $key => $field_definition) {
      if (!$field_definition->getFieldStorageDefinition()->isBaseField()) {
        $tables[] = $entity_type_id . '__' . $key;
      }
    }

    return [
      'id'        => $id,
      'tables'    => $tables,
      'baseTable' => $baseTable,
      'dataTable' => $dataTable,
    ];

  }
}
