<?php

namespace Drupal\vactory_content_sheets\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Vactory migrate drush commands.
 */
class Commands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Commands construct definition.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Disable content sheets data.
   *
   * @command vactory-disable-content-sheets
   * @aliases vdcs
   */
  public function disableContentSheets($options = ['bundles' => '']) {
    $bundles = isset($options['bundles']) && !empty($options['bundles']) ? explode(",", $options['bundles']) : ['vactory_page'];
    // Load nodes.
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $blockStorage = $this->entityTypeManager->getStorage('block_content');
    try {
      $query = $nodeStorage->getQuery()
        ->condition('type', $bundles, 'IN')
        ->accessCheck(FALSE);
      $nodes = $query->execute();
    }
    catch (\Exception $e) {
      $this->output()->writeln($e);
    }

    // Load blocks.
    try {
      $blocks = $blockStorage->getQuery()
        ->accessCheck(FALSE)
        ->execute();
    }
    catch (\Exception $e) {
      $this->output()->writeln($e);
    }

    if (empty($nodes) && empty($blocks)) {
      return;
    }

    $this->processBatch($nodes, NULL);
    $this->processBatch(NULL, $blocks);

  }

  /**
   * Process batch function.
   */
  public function processBatch($nodes = [], $blocks = []) {
    $type = !empty($nodes) ? 'node' : 'block_content';
    $this->output()
      ->writeln('==================' . $type . '=====================');
    $this->output()->writeln('Process of disable content sheets start');
    $operations = [];
    $num_operations = 0;
    $batch_id = 1;
    $items = !empty($nodes) ? $nodes : $blocks;
    foreach ($items as $entity_id) {
      $this->output()
        ->writeln("Preparing batch: [Entity id: " . $entity_id . "]");
      $operations[] = [
        'disable_content_sheets_callback',
        [
          $batch_id,
          $entity_id,
          $type,
          t('Process entity @entity_id', ['@entity_id' => $entity_id]),
        ],
      ];
      $batch_id++;
      $num_operations++;
    }
    if (!empty($operations)) {
      $batch = [
        'title' => 'Process of disable content sheets',
        'operations' => $operations,
        'finished' => 'disable_content_sheets_finished',
      ];
      batch_set($batch);
      drush_backend_batch_process();
      $this->output()->writeln('Process of disable content sheets end.');
    }
  }

}
