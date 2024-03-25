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
   * @command vactory-disbale-content-sheets
   * @aliases vdcs
   */
  public function disableContentSheets() {
    $this->output()->writeln('Process of disable content sheets start...');
    $storage = $this->entityTypeManager->getStorage('node');
    try {
      $query = $storage->getQuery()
        ->condition('type', 'vactory_page')
        ->accessCheck(FALSE);
      $nodes = $query->execute();
    }
    catch (\Exception $e) {
      $this->output()->writeln($e);
    }

    $operations = [];
    $num_operations = 0;
    $batch_id = 1;
    foreach ($nodes as $nid) {
      $this->output()->writeln("Preparing batch: [Node: " . $nid . "]");
      $operations[] = [
        'disable_content_sheets_callback',
        [
          $batch_id,
          $nid,
          t('Process node @nid', ['@nid' => $nid]),
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
