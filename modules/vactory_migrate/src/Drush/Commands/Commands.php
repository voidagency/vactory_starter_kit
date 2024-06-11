<?php

namespace Drupal\vactory_migrate\Drush\Commands;

use Drupal\vactory_migrate\Services\Import;
use Drupal\vactory_migrate\Services\Rollback;
use Drush\Commands\DrushCommands;
use function PHPStan\BetterReflection\Reflection\Adapter\isFinal;

/**
 * Vactory migrate drush commands.
 */
class Commands extends DrushCommands {


  /**
   * @var \Drupal\vactory_migrate\Services\Rollback
   */
  protected $rollbackService;

  /**
   * @var \Drupal\vactory_migrate\Services\Import
   */
  protected $importService;

  public function __construct(Rollback $rollback, Import $import) {
    $this->rollbackService = $rollback;
    $this->importService = $import;
  }

  /**
   * Rollback migration using database service
   *
   * @command vactory-migrate-rollback
   * @aliases vmr
   *
   * @param string $migration ID of migration to rollback.
   */
  public function rollback($migration = '') {
    if ($migration == ''){
      $this->output()->writeln("<error>Error: The '--migration=[migration ID]' option is missing.</error>");
    }else{

      $result = $this->rollbackService->rollback($migration);
      if (isset($result['status']) && $result['status'] == 'error'){
        $this->output->writeln("<error>" . $result['message'] . "</error>");
      }
    }
  }


  /**
   * Import migration using batcj
   *
   * @command vactory-migrate-import
   * @aliases vmim
   *
   * @param string $migration ID of migration to rollback.
   */
  public function import($migration = '') {
    if ($migration == ''){
      $this->output()->writeln("<error>Error: The '--migration=[migration ID]' option is missing.</error>");
    }else{
      $this->importService->import($migration);
    }
  }

}
