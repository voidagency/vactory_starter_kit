<?php

namespace Drupal\vactory_content_diff\Commands;

use Drupal\Core\File\FileSystemInterface;
use Drupal\vactory_content_diff\ContentDiffConst;
use Drush\Commands\DrushCommands;
use Drupal\vactory_content_diff\ContentDiffStatus;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Drush commands for Vactory Content Diff module.
 */
class ContentDiffFetchCommands extends DrushCommands {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new VactoryContentDiffFetchCommand object.
   */
  public function __construct(ClientInterface $http_client, EntityTypeManagerInterface $entity_type_manager) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Fetch and compare content from remote Drupal instance.
   *
   * @command vactory_content_diff_fetch
   * @aliases vcd-fetch
   * @option remote_url Remote instance base URL
   * @usage drush vactory_content_diff_fetch --remote_url=https://remote.example.com
   */
  public function fetch($options = ['remote_url' => NULL]) {
    $remote_url = $options['remote_url'] ?? '';

    if (empty($remote_url)) {
      $this->logger()->error('Remote URL is required. Use --remote_url option.');
      return;
    }

    $this->output()->writeln('Fetching content diff report...');
    $this->output()->writeln('Remote URL: ' . $remote_url);

    // Get content entity types from local instance.
    $content_entity_types = $this->getContentEntityTypes();
    $this->output()->writeln('Found ' . count($content_entity_types) . ' content entity types to process.');

    $all_results = [];
    $total_entities = 0;

    foreach ($content_entity_types as $content_entity_type) {
      $this->output()->writeln('Processing ' . $content_entity_type . ' entities...');

      // Fetch remote entities for this type.
      $remote_entities = $this->fetchRemoteEntities($remote_url, $content_entity_type);

      if (!empty($remote_entities)) {
        $this->output()->writeln('  Fetched ' . count($remote_entities) . ' ' . $content_entity_type . ' entities');

        // Compare with local entities.
        $compared = $this->compareWithLocal($remote_entities, $content_entity_type);
        $all_results = array_merge($all_results, $compared);
        $total_entities += count($remote_entities);
      }
      else {
        $this->output()->writeln('  No ' . $content_entity_type . ' entities found');
      }
    }

    // Generate CSV file.
    $csv_path = $this->generateCsv($all_results);

    $this->output()->writeln('');
    $this->output()->writeln('Fetch complete!');
    $this->output()->writeln('Total entities: ' . $total_entities);
    $this->output()->writeln('Results: ' . count($all_results));
    $this->output()->writeln('CSV file: ' . $csv_path);
  }

  /**
   * Get content entity types that should be fetched.
   */
  protected function getContentEntityTypes(): array {
    return [
      'taxonomy_term',
      'node',
    ];
  }

  /**
   * Fetch remote entities with pagination using HTTP client.
   */
  protected function fetchRemoteEntities(string $remote_url, string $entity_type_id): array {
    $base = rtrim($remote_url, '/');
    $api_endpoint = $base . '/api/' . $entity_type_id;

    $all_entities = [];
    $current_url = $api_endpoint;

    while ($current_url) {
      try {
        $response = $this->httpClient->request('GET', $current_url, [
          'headers' => [
            'Accept' => 'application/vnd.api+json, application/json',
          ],
          'timeout' => 30,
        ]);

        if ($response->getStatusCode() === 200) {
          $data = json_decode((string) $response->getBody(), TRUE);
          $entities = $data['data'] ?? [];

          if (!empty($entities)) {
            $all_entities = array_merge($all_entities, $entities);
          }

          $current_url = $data['links']['next']['href'] ?? NULL;
        }
        else {
          if ($response->getStatusCode() === 404) {
            // Endpoint doesn't exist for this entity type, skip silently.
            break;
          }
          else {
            $this->logger()->warning('HTTP ' . $response->getStatusCode() . ' for ' . $current_url);
            break;
          }
        }
      }
      catch (\Throwable $e) {
        $this->logger()->error('Error fetching ' . $entity_type_id . ': ' . $e->getMessage());
        break;
      }
    }

    return $all_entities;
  }

  /**
   * Compare remote entities with local ones using the same logic as the form.
   */
  protected function compareWithLocal(array $remote_entities, string $entity_type_id): array {
    $results = [];
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    foreach ($remote_entities as $item) {
      $uuid = $item['id'] ?? NULL;
      $attributes = $item['attributes'] ?? [];

      // Get title based on entity type.
      $title = $this->getEntityTitle($attributes, $entity_type_id);

      // Get bundle.
      $bundle = $this->getEntityBundle($item, $entity_type_id);

      // Determine remote changed timestamp.
      $remote_changed_raw = $attributes['changed'] ?? NULL;
      $remote_changed = NULL;
      if ($remote_changed_raw !== NULL) {
        if (is_numeric($remote_changed_raw)) {
          $remote_changed = (int) $remote_changed_raw;
        }
        else {
          $remote_changed = strtotime((string) $remote_changed_raw) ?: NULL;
        }
      }

      // Default status: synchronized.
      $status = ContentDiffStatus::SYNCHRONIZED;

      if ($uuid) {
        $nids = \Drupal::entityQuery($entity_type_id)
          ->condition('uuid', $uuid)
          ->accessCheck(TRUE)
          ->range(0, 1)
          ->execute();

        if (empty($nids)) {
          $status = ContentDiffStatus::NEW_ENTITY;
        }
        else {
          $nid = reset($nids);
          $local_entity = $storage->load($nid);

          if ($local_entity) {
            $local_changed = (int) $local_entity->getChangedTime();
            if ($remote_changed !== NULL && $remote_changed !== $local_changed) {
              $status = ContentDiffStatus::MODIFIED;
            }
            else {
              $status = ContentDiffStatus::SYNCHRONIZED;
            }
          }
        }
      }

      $results[] = [
        'title' => $title,
        'bundle' => $bundle,
        'status_key' => $status['key'],
        'status' => $status['label'],
        'status_class' => $status['class'],
        'uuid' => $uuid,
        'entity_type' => $entity_type_id,
      ];
    }

    return $results;
  }

  /**
   * Get entity title from attributes based on entity type.
   */
  protected function getEntityTitle(array $attributes, string $entity_type_id): string {
    $title_fields = [
      'node' => 'title',
      'taxonomy_term' => 'name',
    ];

    $title_field = $title_fields[$entity_type_id] ?? 'title';
    return (string) ($attributes[$title_field] ?? '');
  }

  /**
   * Get entity bundle from remote entity data.
   */
  protected function getEntityBundle(array $remote_entity, string $entity_type_id): string {
    if ($entity_type_id === 'file') {
      return 'file';
    }

    $type = $remote_entity['type'] ?? '';
    return str_replace($entity_type_id . '--', '', $type);
  }

  /**
   * Generate CSV file with results.
   */
  protected function generateCsv(array $results): string {
    // Utiliser le système de fichiers privé de Drupal.
    $csv_path = ContentDiffConst::FILE_PATH . '/' . ContentDiffConst::FILE_NAME;

    // S'assurer que le répertoire existe.
    $directory = ContentDiffConst::FILE_PATH;
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    $file = fopen($csv_path, 'w');
    if (!$file) {
      $this->logger()->error('Cannot create CSV file: ' . $csv_path);
      return '';
    }

    // Write CSV header (same columns as the form table).
    fputcsv($file, ['title', 'uuid', 'type', 'bundle', 'status']);

    // Write data rows.
    foreach ($results as $row) {
      fputcsv($file, [
        $row['title'],
        $row['uuid'],
        $row['entity_type'],
        $row['bundle'],
        $row['status_key'],
      ]);
    }

    fclose($file);

    return $csv_path;
  }

}
