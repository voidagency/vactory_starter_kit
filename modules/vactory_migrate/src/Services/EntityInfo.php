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
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity fields manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Get migration destination info.
   */
  public function getDestinationByMigrationId($migration_id) {
    $config_prefix = 'migrate_plus.migration.';
    if (str_starts_with($migration_id, $config_prefix)) {
      $migration_id = str_replace($config_prefix, '', $migration_id);
    }
    $migration_config = \Drupal::entityTypeManager()
      ->getStorage('migration')
      ->load($migration_id);
    $migrate_process_clause = $migration_config->get('process');
    $langcode = $this->getMigrationLangcode($migrate_process_clause);
    $destination = $migration_config->get('destination');
    if (!isset($destination)) {
      return [];
    }
    $plugin = $destination['plugin'];
    $entity = explode(':', $plugin)[1];
    return [
      'entity' => $entity,
      'bundle' => array_key_exists('default_bundle', $destination) ? $destination['default_bundle'] : $entity,
      'langcode' => $langcode,
    ];
  }

  /**
   * Get tables for given entity and bundle.
   */
  public function getRelatedTablesByEntityAndBundle($entity_type_id, $bundle) {
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

  /**
   * Get migration language.
   */
  private function getMigrationLangcode(array $data) {
    if (isset($data['langcode']) && is_array($data['langcode'])) {
      $langcode = $data['langcode'];

      if (isset($langcode['plugin']) && $langcode['plugin'] === 'default_value') {
        if (isset($langcode['default_value'])) {
          return $langcode['default_value'];
        }
      }
    }
    return \Drupal::languageManager()->getDefaultLanguage()->getId();
  }

}
