<?php

namespace Drupal\vactory_migrate\Services;


use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateExecutable;

class Import {

  public function __construct() {
  }

  public function import($migration_id) {

    $batch_config = \Drupal::config('vactory_migrate.settings')
      ->get('batch_size');
    $delimiter = \Drupal::config('vactory_migrate.settings')->get('delimiter');
    $batch_size = isset($batch_config) ? $batch_config : 1000;

    //Get migration source path
    $manager = \Drupal::service('plugin.manager.migration');
    $migration = $manager->createInstance($migration_id);
    $source = $migration->getSourceConfiguration();
    $main_path = $source['path'];
    //split main file into batched files and return new paths
    $batched_files_dir = 'private://migrate-csv/' . $migration_id;
    $batched_files = $this->splitCsvFile($main_path, $batched_files_dir, $batch_size, $delimiter);
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
      $result = $executable->import();

      \Drupal::messenger()
        ->addStatus('Failed => ' . $executable->getFailedCount());
      \Drupal::messenger()
        ->addStatus('Created => ' . $executable->getCreatedCount());
      \Drupal::messenger()
        ->addStatus('Ignored => ' . $executable->getIgnoredCount());
      \Drupal::messenger()
        ->addStatus('Processed => ' . $executable->getProcessedCount());

      $url_options = ['absolute' => TRUE];
      $t_args = [
        ':settings_url' => Url::fromUri('base:/admin/structure/migrate/manage/'.$this->getMigrationGroup($migration->id()).'/migrations/' . $migration->id() . '/messages', $url_options)
          ->toString(),
      ];


      $message = t('More information  <a target="_blank" href=":settings_url"> here </a>.', $t_args);

      \Drupal::messenger()->addStatus($message);
      if ($result == MigrationInterface::RESULT_FAILED) {
        \Drupal::messenger()->addStatus('Migration failed.');
      }
      $context['results']['batched_files_dir'] = $batched_files_dir;
    } catch (\Exception $e) {
      \Drupal::messenger()->addStatus($e->getMessage());
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


  private function splitCsvFile($filePath, $outputDir, $linesPerFile, $delimiter) {

    $sourceFile = fopen($filePath, 'r');
    $header = fgetcsv($sourceFile, NULL, $delimiter);

    $fileNumber = 1;
    $lineCount = 0;
    $outputFile = NULL;
    $outputFiles = [];

    if (!file_exists($outputDir)) {
      mkdir($outputDir, 0777);
    }

    while (($data = fgetcsv($sourceFile, NULL, $delimiter)) !== FALSE) {
      if ($lineCount % $linesPerFile === 0) {
        if (isset($outputFile)) {
          fclose($outputFile);
        }
        $outputFilePath = $outputDir . '/output_' . $fileNumber . '.csv';
        $outputFile = fopen($outputFilePath, 'w');
        fputcsv($outputFile, $header, $delimiter);
        $outputFiles[] = $outputFilePath;
        $fileNumber++;
      }
      fputcsv($outputFile, $data, $delimiter);
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
    $output_dir = str_replace('private://', '', $dirUri);
    if ($dirPath && is_dir($dirPath) && $dirPath !== DRUPAL_ROOT && str_ends_with($dirPath, $output_dir)) {
      $fileSystem->deleteRecursive($dirPath);
    }
  }

  private function getMigrationGroup($migration_id){
    $config = \Drupal::configFactory()->get('migrate_plus.migration.' . $migration_id);
    $group = $config->get('migration_group');
    return $group ?? 'default';
  }

}