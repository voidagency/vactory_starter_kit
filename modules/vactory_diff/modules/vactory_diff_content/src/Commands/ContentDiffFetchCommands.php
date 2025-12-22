<?php

namespace Drupal\vactory_diff_content\Commands;

use Drupal\vactory_diff_content\Service\ContentDiffService;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Vactory Content Diff module.
 */
class ContentDiffFetchCommands extends DrushCommands {

  /**
   * Diff service.
   *
   * @var \Drupal\vactory_diff_content\Service\ContentDiffService
   */
  protected $diffService;

  /**
   * Constructs a new VactoryContentDiffFetchCommand object.
   */
  public function __construct(ContentDiffService $diffService) {
    parent::__construct();
    $this->diffService = $diffService;
  }

  /**
   * Fetch and compare content from remote Drupal instance.
   *
   * @command vactory_diff_content_fetch
   * @aliases vcd-fetch
   * @usage drush vactory_diff_content_fetch
   */
  public function fetch() {
    $this->output()->writeln('Fetching content diff report...');

    // Use the service method that orchestrates the entire process.
    $result = $this->diffService->generateDiffReport();

    if (!$result['success']) {
      $this->logger()->error($result['message']);
      return;
    }

    $this->output()->writeln('Remote URL: ' . $result['remote_url']);
    $this->output()->writeln('');
    $this->output()->writeln('Fetch complete!');
    $this->output()->writeln('Total entities: ' . $result['total_entities']);
    $this->output()->writeln('Results: ' . $result['results_count']);
    $this->output()->writeln('CSV file: ' . $result['csv_path']);
  }

}
