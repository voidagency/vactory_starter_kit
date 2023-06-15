<?php

namespace Drupal\vactory_migrate\Services;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;

class Import {

  public function __construct() {
  }

  public function import($migration_id) {

    $batch_config = \Drupal::config('vactory_migrate.settings')->get('batch_size');
    $batch_size = isset($batch_config) ? $batch_config : 1000 ;

    //Get migration source path
    $manager = \Drupal::service('plugin.manager.migration');
    $migration = $manager->createInstance($migration_id);
    $source = $migration->getSourceConfiguration();
    $main_path = $source['path'];
    //split main file into batched files and return new paths
    $batched_files_dir = 'private://' . $migration_id;
    $batched_files = $this->splitCsvFile($main_path, $batched_files_dir, $batch_size);
    //create batch with those files
    $operations = [];
    $num_operations = 0;
    foreach ($batched_files as $file) {
      $operations[] = [
        [$this, 'importCallback'],
        [$file, $migration, $source, $batched_files_dir],
      ];
      $num_operations++;
    }
    if (!empty($operations)) {
      $batch = [
        'title'      => 'Process of importing',
        'operations' => $operations,
        'finished'   => [$this, 'importFinished'],
      ];
      batch_set($batch);
      if (php_sapi_name() === 'cli') {
        drush_backend_batch_process();
      }
    }
  }

  public function importCallback($file, $migration, $source, $batched_files_dir, &$context) {
    $source['path'] = $file;
    $migration->set('source', $source);
    $migration->getIdMap()->prepareUpdate();

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    try {
      $executable->import();
      $context['results']['batched_files_dir'] = $batched_files_dir;
    } catch (\Exception $e) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
    }
  }

  public function importFinished($success, $results, $operations) {
    if ($success) {
      $batched_files_dir = $results['batched_files_dir'];
      $this->deleteDirectoryByUri($batched_files_dir);
      $message = "Import process finished successfully.";
      \Drupal::messenger()->addStatus($message);
    }
  }


  private function splitCsvFile($filePath, $outputDir, $linesPerFile) {

    $sourceFile = fopen($filePath, 'r');
    $header = fgetcsv($sourceFile);

    $fileNumber = 1;
    $lineCount = 0;
    $outputFile = NULL;
    $outputFiles = [];

    if (!file_exists($outputDir)) {
      mkdir($outputDir, 0777);
    }

    while (($data = fgetcsv($sourceFile)) !== FALSE) {
      if ($lineCount % $linesPerFile === 0) {
        if (isset($outputFile)) {
          fclose($outputFile);
        }
        $outputFilePath = $outputDir . '/output_' . $fileNumber . '.csv';
        $outputFile = fopen($outputFilePath, 'w');
        fputcsv($outputFile, $header);
        $outputFiles[] = $outputFilePath;
        $fileNumber++;
      }

      fputcsv($outputFile, $data);
      $lineCount++;
    }

    fclose($sourceFile);
    if (isset($outputFile)) {
      fclose($outputFile);
    }

    return $outputFiles;
  }

  private function deleteDirectoryByUri($dirUri) {
    $fileSystem = \Drupal::service('file_system');
    $dirPath = $fileSystem->realpath($dirUri);
    if ($dirPath && is_dir($dirPath)) {
      $fileSystem->deleteRecursive($dirPath);
    }
  }

}