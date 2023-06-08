<?php

namespace Drupal\vactory_migrate\Services;

use Drupal\Core\Database\Connection;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;

class Rollback {


  /**
   * @var \Drupal\vactory_migrate\Services\EntityInfo
   */
  protected $entityInfo;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(EntityInfo $entityInfo, Connection $database) {
    $this->entityInfo = $entityInfo;
    $this->database = $database;
  }

  public function rollback($migration_id, $re_import = FALSE) {

    $mapping_table = 'migrate_map_' . $migration_id;
    $message_table = 'migrate_message_' . $migration_id;

    //Get entity info.
    $destination = $this->entityInfo->getDestinationByMigrationId($migration_id);
    if (empty($destination)) {
      return [
        'status'  => 'error',
        'message' => 'cannot load migration config with id = ' . $migration_id,
      ];
    }
    $entity_type_id = $destination['entity'];
    $bundle = $destination['bundle'];

    $tableInfo = $this->entityInfo->getRelatedTablesByEntityAndBundle($entity_type_id, $bundle);

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
    $re_import_info = [
      're_import'    => $re_import,
      'migration_id' => $migration_id,
    ];
    if (!empty($rows)) {
      $chunk = array_chunk($rows, 100);
      foreach ($chunk as $ids) {
        $operations[] = [
          [static::class, 'rollbackCallback'],
          [$ids, $tableInfo, $mapping_table, $message_table, $re_import_info],
        ];
        $num_operations++;
      }
      if (!empty($operations)) {
        $batch = [
          'title'      => 'Process of cleaning expired bourse data',
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


  public static function rollbackCallback($ids, $tableInfo, $mapping_table, $message_table, $re_import_info, &$context) {
    $column_id = $tableInfo['id'];
    $tables = $tableInfo['tables'];
    $baseTable = $tableInfo['baseTable'];
    $dataTable = $tableInfo['dataTable'];

    foreach ($tables as $table) {
      self::dbDelete($table, 'entity_id', $ids, 'IN');
    }

    // Delete from users_field_data table.
    if (isset($dataTable)) {
      self::dbDelete($dataTable, $column_id, $ids, 'IN');
    }

    // Delete from users table.
    if (isset($baseTable)) {
      self::dbDelete($baseTable, $column_id, $ids, 'IN');
    }
    // Delete messages && mapping.

    self::dbDelete($mapping_table, 'destid1', $ids, 'IN');
    // todo delete tables ??
    //    self::dropTable($mapping_table);
    //    self::dropTable($message_table);


    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += count($ids);
    $context['results']['re_import'] = $re_import_info;

    // todo clear cache

  }

  public static function rollbackFinished($success, $results, $operations) {
    if ($success) {

      $re_import_info = $results['re_import'];
      if ($re_import_info['re_import']) {
        $migration_id = $re_import_info['migration_id'];
        $manager = \Drupal::service('plugin.manager.migration');
        $migration = $manager->createInstance($migration_id);
        $migration->getIdMap()->prepareUpdate();
        $executable = new MigrateExecutable($migration, new MigrateMessage());

        try {
          $res = $executable->import();
          if ($res) {
            $message = "imported successfully";
            \Drupal::messenger()->addStatus($message);
          }
        } catch (\Exception $e) {
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
        }
      }

      $message = "Rollback finished: {$results['count']} items deleted.";
      \Drupal::messenger()->addStatus($message);
    }
  }


  public static function dbDelete($table, $column, $id, $operator = '=') {
    $databaseService = \Drupal::service('database');
    $transaction = $databaseService->startTransaction();
    try {
      $databaseService->delete($table)
        ->condition($column, $id, $operator)
        ->execute();
    } catch (\Exception $e) {
      $transaction->rollBack();
      throw new \Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  public static function dropTable($table) {
    $databaseService = \Drupal::service('database');
    $databaseService->schema()->dropTable($table);
  }
}