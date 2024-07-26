<?php

namespace Drupal\vactory_migrate\Services;

use Drupal\Core\Database\Connection;

/**
 * Rollback Service using database service.
 */
class Rollback {

  /**
   * Entity info Service.
   *
   * @var \Drupal\vactory_migrate\Services\EntityInfo
   */
  protected $entityInfo;

  /**
   * Database Service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Rollback constructor.
   */
  public function __construct(EntityInfo $entityInfo, Connection $database) {
    $this->entityInfo = $entityInfo;
    $this->database = $database;
  }

  /**
   * Rollback Service using database service.
   */
  public function rollback($migration_id) {

    $mapping_table = 'migrate_map_' . $migration_id;
    $message_table = 'migrate_message_' . $migration_id;
    $batch_config = \Drupal::config('vactory_migrate.settings')
      ->get('batch_size');
    $batch_size = isset($batch_config) ? $batch_config : 1000;

    // Get entity info.
    $destination = $this->entityInfo->getDestinationByMigrationId($migration_id);
    if (empty($destination)) {
      return [
        'status'  => 'error',
        'message' => 'cannot load migration config with id = ' . $migration_id,
      ];
    }
    $entity_type_id = $destination['entity'];
    $bundle = $destination['bundle'];
    $langcode = $destination['langcode'];

    $tableInfo = $this->entityInfo->getRelatedTablesByEntityAndBundle($entity_type_id, $bundle);

    if (!$this->database->schema()->tableExists($mapping_table)) {
      return;
    }

    // Add index to increase performance.
    $index_name = 'destination';
    if (!$this->database->schema()->indexExists($mapping_table, $index_name)) {
      $this->database->query('ALTER TABLE {' . $mapping_table . '} ADD INDEX `' . $index_name . '` (destid1)');
    }

    // Query rows.
    $query = $this->database->query("SELECT destid1 FROM ${mapping_table} WHERE destid1 IS NOT NULL");
    $rows = $query->fetchAll(\PDO::FETCH_COLUMN, 0);

    $operations = [];
    $num_operations = 0;

    if (!empty($rows)) {
      $chunk = array_chunk($rows, $batch_size);
      foreach ($chunk as $ids) {
        $operations[] = [
          [static::class, 'rollbackCallback'],
          [$ids, $tableInfo, $mapping_table, $message_table, $langcode],
        ];
        $num_operations++;
      }
      if (!empty($operations)) {
        $batch = [
          'title'      => 'Process of Rolling back',
          'operations' => $operations,
          'finished'   => [static::class, 'rollbackFinished'],
        ];
        batch_set($batch);
        if (php_sapi_name() === 'cli') {
          drush_backend_batch_process();
        }
      }
    }
  }

  /**
   * Batch callback.
   */
  public static function rollbackCallback($ids, $tableInfo, $mapping_table, $message_table, $langcode, &$context) {
    $column_id = $tableInfo['id'];
    $tables = $tableInfo['tables'];
    $baseTable = $tableInfo['baseTable'];
    $dataTable = $tableInfo['dataTable'];

    foreach ($tables as $table) {
      self::dbDelete($table, 'entity_id', $ids, 'IN', $langcode);
    }

    // Delete from users_field_data table.
    if (isset($dataTable)) {
      self::dbDelete($dataTable, $column_id, $ids, 'IN', $langcode);
    }

    // Delete from users table.
    if (isset($baseTable)) {
      self::dbDelete($baseTable, $column_id, $ids, 'IN', $langcode);
    }

    // Delete messages && mapping.
    self::dbDelete($mapping_table, 'destid1', $ids, 'IN');
    self::dropTable($mapping_table);
    self::dropTable($message_table);

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += count($ids);

    drupal_flush_all_caches();

  }

  /**
   * Batch finished callback.
   */
  public static function rollbackFinished($success, $results, $operations) {
    if ($success) {
      $message = "Rollback finished: {$results['count']} items deleted.";
      \Drupal::messenger()->addStatus($message);
    }
  }

  /**
   * Delete database table.
   */
  public static function dbDelete($table, $column, $id, $operator = '=', $langcode = '') {
    $databaseService = \Drupal::service('database');
    $transaction = $databaseService->startTransaction();
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    try {
      $delete_query = $databaseService->delete($table)
        ->condition($column, $id, $operator);
      if (!empty($langcode) && $langcode !== $default_langcode) {
        $delete_query->condition('langcode', $langcode);
      }
      $delete_query->execute();
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw new \Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Drop database table.
   */
  public static function dropTable($table) {
    $databaseService = \Drupal::service('database');
    if ($databaseService->schema()->tableExists($table)) {
      $databaseService->schema()->dropTable($table);
    }
  }

}
