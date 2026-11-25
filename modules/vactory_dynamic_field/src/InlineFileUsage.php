<?php

namespace Drupal\vactory_dynamic_field;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * Records the usage of files inserted in the editors of Dynamic Fields.
 *
 * The core editor module does it for formatted text fields only. The HTML of
 * a Dynamic Field is stored in the JSON of "widget_data", which it does not
 * read: an image uploaded from CKEditor stayed a temporary file without usage.
 *
 * The usage is recorded for the "editor" module, as core does for formatted
 * text fields, so that editor_file_download() also covers these files.
 */
class InlineFileUsage {

  /**
   * The Dynamic Field field type.
   */
  const FIELD_TYPE = 'field_wysiwyg_dynamic';

  /**
   * The module the usage is recorded for.
   */
  const USAGE_MODULE = 'editor';

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The file usage service, NULL when the file module is not installed.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface|null
   */
  protected $fileUsage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs the service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, ?FileUsageInterface $file_usage, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Connection $database) {
    $this->entityRepository = $entity_repository;
    $this->fileUsage = $file_usage;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $database;
  }

  /**
   * Finds the files referenced (data-entity-uuid) in a widget data JSON.
   *
   * Every string of the data is inspected, whatever its depth: simple fields,
   * groups, multiple components and extra fields.
   *
   * @param string|null $widget_data
   *   The JSON stored in the "widget_data" property.
   *
   * @return string[]
   *   The UUIDs of the referenced files, without duplicates.
   */
  public function parseWidgetData(?string $widget_data): array {
    if (empty($widget_data) || !str_contains($widget_data, 'data-entity-uuid')) {
      return [];
    }
    $data = Json::decode($widget_data);
    if (!is_array($data)) {
      return [];
    }
    $html = '';
    array_walk_recursive($data, function ($value) use (&$html) {
      if (is_string($value) && str_contains($value, 'data-entity-uuid')) {
        $html .= $value;
      }
    });
    if ($html === '') {
      return [];
    }
    $uuids = [];
    $xpath = new \DOMXPath(Html::load($html));
    foreach ($xpath->query('//*[@data-entity-type="file" and @data-entity-uuid]') as $node) {
      $uuids[$node->getAttribute('data-entity-uuid')] = TRUE;
    }
    return array_keys($uuids);
  }

  /**
   * Finds the files referenced by the Dynamic Fields of an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity, all its translations are inspected.
   *
   * @return array
   *   File UUIDs without duplicates, keyed by field name. Empty when the
   *   entity has no Dynamic Field.
   */
  public function getFileUuidsByField(FieldableEntityInterface $entity): array {
    $uuids = [];
    if (!$this->fileUsage) {
      return $uuids;
    }
    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->getType() !== self::FIELD_TYPE) {
        continue;
      }
      $uuids[$field_name] = [];
      $translations = [$entity];
      if ($entity instanceof TranslatableInterface && $definition->isTranslatable()) {
        $translations = [];
        foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
          $translations[] = $entity->getTranslation($langcode);
        }
      }
      foreach ($translations as $translation) {
        foreach ($translation->get($field_name) as $item) {
          $uuids[$field_name] = array_merge($uuids[$field_name], $this->parseWidgetData($item->widget_data));
        }
      }
      $uuids[$field_name] = array_values(array_unique($uuids[$field_name]));
    }
    return $uuids;
  }

  /**
   * Records the files of a new entity.
   */
  public function entityInsert(FieldableEntityInterface $entity): void {
    foreach ($this->getFileUuidsByField($entity) as $uuids) {
      $this->record($uuids, $entity);
    }
  }

  /**
   * Updates the usage when an entity is saved, like editor_entity_update().
   */
  public function entityUpdate(FieldableEntityInterface $entity): void {
    $uuids_by_field = $this->getFileUuidsByField($entity);
    if (!$uuids_by_field) {
      return;
    }
    $original = $entity->original ?? NULL;
    // On a new revision every file is a new usage.
    if ($original && $entity->getRevisionId() != $original->getRevisionId()) {
      foreach ($uuids_by_field as $uuids) {
        $this->record($uuids, $entity);
      }
      return;
    }
    // On a modified revision, only the added and the removed files change.
    $original_uuids_by_field = $original instanceof FieldableEntityInterface ? $this->getFileUuidsByField($original) : [];
    foreach ($uuids_by_field as $field_name => $uuids) {
      $this->record(array_diff($uuids, $original_uuids_by_field[$field_name] ?? []), $entity);
    }
    foreach ($original_uuids_by_field as $field_name => $original_uuids) {
      $this->delete(array_diff($original_uuids, $uuids_by_field[$field_name] ?? []), $entity, 1);
    }
  }

  /**
   * Removes the usage of the files of a deleted entity.
   */
  public function entityDelete(FieldableEntityInterface $entity): void {
    foreach ($this->getFileUuidsByField($entity) as $uuids) {
      $this->delete($uuids, $entity, 0);
    }
  }

  /**
   * Removes one usage of the files of a deleted revision.
   */
  public function entityRevisionDelete(FieldableEntityInterface $entity): void {
    foreach ($this->getFileUuidsByField($entity) as $uuids) {
      $this->delete($uuids, $entity, 1);
    }
  }

  /**
   * Makes the files permanent and records their usage by the entity.
   */
  protected function record(array $uuids, EntityInterface $entity): void {
    foreach ($uuids as $uuid) {
      /** @var \Drupal\file\FileInterface|null $file */
      if ($file = $this->entityRepository->loadEntityByUuid('file', $uuid)) {
        if ($file->isTemporary()) {
          $file->setPermanent();
          $file->save();
        }
        $this->fileUsage->add($file, self::USAGE_MODULE, $entity->getEntityTypeId(), $entity->id());
      }
    }
  }

  /**
   * Removes usages of the files by the entity.
   *
   * @param array $uuids
   *   File UUIDs.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity using the files.
   * @param int $count
   *   Use 1 for a single revision, 0 to remove every usage by the entity.
   */
  protected function delete(array $uuids, EntityInterface $entity, int $count): void {
    foreach ($uuids as $uuid) {
      if ($file = $this->entityRepository->loadEntityByUuid('file', $uuid)) {
        $this->fileUsage->delete($file, self::USAGE_MODULE, $entity->getEntityTypeId(), $entity->id(), $count);
      }
    }
  }

  /**
   * Records the files of the content saved before this service existed.
   *
   * Reads the Dynamic Fields in their SQL tables, without loading the
   * entities. Every revision is read, in every language: as core does for
   * formatted text fields, a file counts one usage per revision of the entity
   * that references it, so that deleting a revision later removes the usage
   * of that revision only. A usage already recorded for the entity is
   * deducted: the method can run several times without counting a file twice.
   *
   * @param array $sandbox
   *   The sandbox of the update hook, "#finished" is set.
   * @param int $limit
   *   Number of entities per pass.
   *
   * @return int
   *   The number of usages recorded so far.
   */
  public function recordExistingContent(array &$sandbox, int $limit = 50): int {
    if (!isset($sandbox['fields'])) {
      $sandbox['fields'] = [];
      $field_map = $this->fileUsage ? $this->entityFieldManager->getFieldMapByFieldType(self::FIELD_TYPE) : [];
      foreach ($field_map as $entity_type_id => $fields) {
        foreach (array_keys($fields) as $field_name) {
          $sandbox['fields'][] = [$entity_type_id, $field_name];
        }
      }
      $sandbox['current'] = 0;
      $sandbox['last_id'] = NULL;
      $sandbox['recorded'] = 0;
    }
    if (!isset($sandbox['fields'][$sandbox['current']])) {
      $sandbox['#finished'] = 1;
      return $sandbox['recorded'];
    }

    [$entity_type_id, $field_name] = $sandbox['fields'][$sandbox['current']];
    $ids = [];
    $location = $this->getFieldTable($entity_type_id, $field_name);
    if ($location) {
      [$table, $column] = $location;
      $query = $this->database->select($table, 't')
        ->fields('t', ['entity_id'])
        ->condition('t.' . $column, '%data-entity-uuid%', 'LIKE')
        ->distinct()
        ->orderBy('t.entity_id')
        ->range(0, $limit);
      if ($sandbox['last_id'] !== NULL) {
        $query->condition('t.entity_id', $sandbox['last_id'], '>');
      }
      $ids = $query->execute()->fetchCol();
    }
    if (!$ids) {
      // Next field.
      $sandbox['current']++;
      $sandbox['last_id'] = NULL;
    }
    else {
      foreach ($this->countRevisionsByFile($location, $ids) as $entity_id => $counts) {
        foreach ($counts as $uuid => $count) {
          $sandbox['recorded'] += $this->recordMissing($uuid, $entity_type_id, $entity_id, $count);
        }
      }
      $sandbox['last_id'] = end($ids);
    }
    $sandbox['#finished'] = $sandbox['current'] >= count($sandbox['fields']) ? 1 : $sandbox['current'] / count($sandbox['fields']);
    return $sandbox['recorded'];
  }

  /**
   * Counts the revisions of entities that reference each file.
   *
   * @param string[] $location
   *   The table, the "widget_data" column and the revision column, as
   *   returned by getFieldTable().
   * @param array $ids
   *   The entity IDs.
   *
   * @return array
   *   Numbers of revisions, keyed by entity ID and file UUID. The languages of
   *   a revision count once.
   */
  protected function countRevisionsByFile(array $location, array $ids): array {
    [$table, $column, $revision_column] = $location;
    $rows = $this->database->select($table, 't')
      ->fields('t', array_unique(['entity_id', $revision_column, $column]))
      ->condition('t.entity_id', $ids, 'IN')
      ->condition('t.' . $column, '%data-entity-uuid%', 'LIKE')
      ->execute();
    $revisions = [];
    foreach ($rows as $row) {
      foreach ($this->parseWidgetData($row->{$column}) as $uuid) {
        $revisions[$row->entity_id][$uuid][$row->{$revision_column}] = TRUE;
      }
    }
    return array_map(fn(array $by_uuid) => array_map('count', $by_uuid), $revisions);
  }

  /**
   * Returns the table, the "widget_data" column and the revision column.
   *
   * The revision table when the field has one, so that every revision is
   * read; otherwise the data table, where an entity has a single revision.
   *
   * @return string[]|null
   *   [table, widget data column, revision column], or NULL when the field is
   *   not stored in a dedicated SQL table.
   */
  protected function getFieldTable(string $entity_type_id, string $field_name): ?array {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $definition = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id)[$field_name] ?? NULL;
    if (!$storage instanceof SqlEntityStorageInterface || !$definition) {
      return NULL;
    }
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping();
    $column = $table_mapping->getFieldColumnName($definition, 'widget_data');
    $tables = [];
    if ($table_mapping->requiresDedicatedTableStorage($definition)) {
      if ($storage->getEntityType()->isRevisionable() && $definition->isRevisionable()) {
        $tables[$table_mapping->getDedicatedRevisionTableName($definition)] = 'revision_id';
      }
      $tables[$table_mapping->getDedicatedDataTableName($definition)] = 'entity_id';
    }
    foreach ($tables as $table => $revision_column) {
      if ($this->database->schema()->tableExists($table)) {
        return [$table, $column, $revision_column];
      }
    }
    return NULL;
  }

  /**
   * Records the usages of a file by an entity that are not recorded yet.
   *
   * @param string $uuid
   *   The file UUID.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int|string $entity_id
   *   The entity ID.
   * @param int $expected
   *   The number of usages the entity must have: its revisions that reference
   *   the file.
   *
   * @return int
   *   The number of usages added.
   */
  protected function recordMissing(string $uuid, string $entity_type_id, $entity_id, int $expected): int {
    /** @var \Drupal\file\FileInterface|null $file */
    $file = $this->entityRepository->loadEntityByUuid('file', $uuid);
    $usage = $file ? $this->fileUsage->listUsage($file) : [];
    $missing = $file ? $expected - (int) ($usage[self::USAGE_MODULE][$entity_type_id][$entity_id] ?? 0) : 0;
    if ($missing < 1) {
      return 0;
    }
    if ($file->isTemporary()) {
      $file->setPermanent();
      $file->save();
    }
    $this->fileUsage->add($file, self::USAGE_MODULE, $entity_type_id, $entity_id, $missing);
    return $missing;
  }

}
