<?php

namespace Drupal\vactory_cloudinary\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class VactoryCloudinaryMoveFiles extends DrushCommands {
  use StringTranslationTrait;

  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * Drush command service constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerChannelFactory
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
  }

  /**
   * Move existing public files to cloudinary.
   *
   * @command move-to-cloudinary
   * @aliases mtc
   * @options test-file
   *  For testing script with the given file ID
   * @options batch-mode
   *  Execute drush command in batch mode.
   * @options source
   *  Specify the migration source, by default the source is drupal
   *  if you wish recover back files from cloudinary then set source to cloudinary.
   *
   * @usage mtc
   * @usage mtc --test-file=123
   *   Move the test file with 123 to cloudinary.
   * @usage mtc --source=cloudinary
   *   Move files from cloudinary to Drupal file system.
   * @usage mtc --batch-mode
   *   Move files from Drupal file system to cloudinary using batch mode.
   */
  public function movePublicFiles($options = ['test-file' => '', 'batch-mode' => FALSE, 'source' => 'drupal']) {
    if (\Drupal::moduleHandler()->moduleExists('cloudinary')) {
      $this->loggerChannelFactory->get('vactory_cloudinary')
        ->info('Moving public files batch operations start');
      $this->output()->writeln('Moving public files batch operations start...');
      try {
        $storage = $this->entityTypeManager->getStorage('file');
        $schema = $options['source'] === 'cloudinary' ? 'cloudinary://' : 'public://';
        $query = $storage->getQuery()
          ->condition('status', '1')
          ->condition('type', 'image')
          ->condition('uri', $schema . '%', 'LIKE')
          ->accessCheck(FALSE);
        $fids = $query->execute();
      } catch (\Exception $e) {
        $this->output()->writeln($e);
        $this->loggerChannelFactory->get('vactory_cloudinary')
          ->warning('Error while loading files: @e', ['@e' => $e]);
      }
      if (isset($options['test-file']) && !empty($options['test-file'])) {
        $fids = [$options['test-file']];
      }
      $source = $options['source'];
      $operations = [];
      $num_operations = 0;
      if (!empty($fids)) {
        if ($options['batch-mode']) {
          foreach ($fids as $fid) {
            $operations[] = [
              'vactory_cloudinary_batch_process',
              [
                $fid,
                'Moving file of ID ' . $fid . ' ...',
                $source
              ],
            ];
            $batch_id++;
            $num_operations++;
          }
          if (!empty($operations)) {
            $batch = [
              'title'      => 'Process of moving public file to cloudinary',
              'operations' => $operations,
              'finished'   => 'vactory_cloudinary_move_file_finished',
            ];
            batch_set($batch);
            drush_backend_batch_process();
            $this->output()->writeln('Moving public files batch operations end.');
            $this->loggerChannelFactory->get('vactory_cloudinary')
              ->info('Moving public files batch operations end.');
          }
        }
        else {
          $files = $this->entityTypeManager->getStorage('file')
            ->loadMultiple($fids);
          $files = array_values($files);
          $statistics = [
            'public_files_count' => 0,
            'moved_files_count' => 0,
            'remained_files_count' => 0,
          ];
          foreach ($files as $file) {
            vactory_cloudinary_move_file($file, $source, $statistics, $this->output());
          }
          $this->output()->writeln('<info>-------------------------------------------</info>');
          $this->output()->writeln('<info>Total public files: ' . $statistics['public_files_count'] . '</info>');
          $this->output()->writeln('<info>Moved files: ' . $statistics['moved_files_count'] . '</info>');
          $this->output()->writeln('<info>Remaining files: ' . $statistics['remained_files_count'] . '</info>');
          $this->output()->writeln('<info>-------------------------------------------</info>');
        }
      }
      else {
        $this->output()->writeln('No files has been found');
        $this->loggerChannelFactory->get('vactory_cloudinary')
          ->info('No files has been found');
      }
    }
    else {
      $this->output()->writeln('You have to enable and configure cloudinary module.');
    }
  }
}
