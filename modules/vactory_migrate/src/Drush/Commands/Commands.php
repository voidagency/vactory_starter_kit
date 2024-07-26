<?php

namespace Drupal\vactory_migrate\Drush\Commands;

use Drupal\vactory_migrate\Services\Import;
use Drupal\vactory_migrate\Services\Rollback;
use Drush\Commands\DrushCommands;

/**
 * Vactory migrate drush commands.
 */
class Commands extends DrushCommands {

  /**
   * Rollback service.
   *
   * @var \Drupal\vactory_migrate\Services\Rollback
   */
  protected $rollbackService;

  /**
   * Import service.
   *
   * @var \Drupal\vactory_migrate\Services\Import
   */
  protected $importService;

  /**
   * Constructor.
   */
  public function __construct(Rollback $rollback, Import $import) {
    $this->rollbackService = $rollback;
    $this->importService = $import;
  }

  /**
   * Rollback migration using database service.
   *
   * @param string $migration
   *   ID of migration to rollback.
   *
   * @command vactory-migrate-rollback
   * @aliases vmr
   */
  public function rollback($migration = '') {
    if ($migration == '') {
      $this->output()->writeln("<error>Error: The '--migration=[migration ID]' option is missing.</error>");
    }
    else {
      $result = $this->rollbackService->rollback($migration);
      if (isset($result['status']) && $result['status'] == 'error') {
        $this->output->writeln("<error>" . $result['message'] . "</error>");
      }
    }
  }

  /**
   * Import migration using batch.
   *
   * @param string $migration
   *   ID of migration to rollback.
   *
   * @command vactory-migrate-import
   * @aliases vmim
   */
  public function import($migration = '') {
    if ($migration == '') {
      $this->output()->writeln("<error>Error: The '--migration=[migration ID]' option is missing.</error>");
    }
    else {
      $this->importService->import($migration);
    }
  }

}
