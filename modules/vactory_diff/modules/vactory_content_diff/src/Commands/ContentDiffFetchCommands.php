<?php

namespace Drupal\vactory_content_diff\Commands;

use Drupal\vactory_content_diff\Service\ContentDiffService;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Vactory Content Diff module.
 */
class ContentDiffFetchCommands extends DrushCommands {

  /**
   * Diff service.
   *
   * @var \Drupal\vactory_content_diff\Service\ContentDiffService
   */
  protected $diffService;

  /**
   * Constructs a new VactoryContentDiffFetchCommand object.
   */
  public function __construct(ContentDiffService $diffService) {
    parent::__construct();
    $this->diff_service = $diffService;
  }

  /**
   * Fetch and compare content from remote Drupal instance.
   *
   * @command vactory_content_diff_fetch
   * @aliases vcd-fetch
   * @usage drush vactory_content_diff_fetch
   */
  public function fetch() {
    $remote_url = \Drupal::service('state')->get('vactory_content_diff.remote_url', '');

    if (empty($remote_url)) {
      $this->logger()->error('Remote URL is required. Please go to /admin/content/diff and configure it.');
      return;
    }

    $this->output()->writeln('Fetching content diff report...');
    $this->output()->writeln('Remote URL: ' . $remote_url);

    // Get content entity types from local instance.
    $content_entity_types = $this->diff_service->getContentEntityTypes();
    $this->output()->writeln('Found ' . count($content_entity_types) . ' content entity types to process.');

    $all_results = [];
    $total_entities = 0;

    foreach ($content_entity_types as $content_entity_type) {
      $this->output()->writeln('Processing ' . $content_entity_type . ' entities...');

      // Fetch remote entities for this type.
      $remote_entities = $this->diff_service->fetchRemoteEntities($remote_url, $content_entity_type);

      if (!empty($remote_entities)) {
        $this->output()->writeln('  Fetched ' . count($remote_entities) . ' ' . $content_entity_type . ' entities');

        // Compare with local entities.
        $compared = $this->diff_service->compareWithLocal($remote_entities, $content_entity_type);
        $all_results = array_merge($all_results, $compared);
        $total_entities += count($remote_entities);
      }
      else {
        $this->output()->writeln('  No ' . $content_entity_type . ' entities found');
      }
    }

    // Generate CSV file.
    $csv_path = $this->diff_service->generateCsv($all_results);

    $this->output()->writeln('');
    $this->output()->writeln('Fetch complete!');
    $this->output()->writeln('Total entities: ' . $total_entities);
    $this->output()->writeln('Results: ' . count($all_results));
    $this->output()->writeln('CSV file: ' . $csv_path);
  }

}
