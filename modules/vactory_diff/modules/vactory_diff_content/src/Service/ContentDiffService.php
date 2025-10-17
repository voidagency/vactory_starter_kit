<?php

namespace Drupal\vactory_diff_content\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\vactory_diff_content\ContentDiffConst;
use GuzzleHttp\ClientInterface;

/**
 * Service for fetching remote content and comparing with local nodes.
 */
class ContentDiffService {

  use StringTranslationTrait;

  /**
   * HTTP client for remote requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Messenger service for user messages.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the service.
   */
  public function __construct(
    ClientInterface $http_client,
    MessengerInterface $messenger,
    $logger_factory,
    TranslationInterface $string_translation,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->httpClient = $http_client;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    // Resolve a concrete logger channel.
    if (method_exists($logger_factory, 'get')) {
      $this->logger = $logger_factory->get('vactory_diff_content');
    }
    else {
      $this->logger = $logger_factory;
    }
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get content entity types that should be fetched.
   */
  public function getContentEntityTypes(): array {
    // Get configured entity types from settings.
    $config = \Drupal::config('vactory_diff_config_client.settings');
    $configured_types = $config->get('content_entity_types');

    // If no configuration, return empty array (user must configure).
    return !empty($configured_types) ? $configured_types : [];
  }

  /**
   * Fetch remote entities with pagination using HTTP client.
   */
  public function fetchRemoteEntities(string $remote_url, string $entity_type_id): array {
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
            $this->logger->warning('HTTP ' . $response->getStatusCode() . ' for ' . $current_url);
            break;
          }
        }
      }
      catch (\Throwable $e) {
        $this->logger->error('Error fetching ' . $entity_type_id . ': ' . $e->getMessage());
        break;
      }
    }

    return $all_entities;
  }

  /**
   * Compare remote entities with local ones using the same logic as the form.
   */
  public function compareWithLocal(array $remote_entities, string $entity_type_id): array {
    $results = [];
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    // Build index of remote UUIDs during processing.
    $remote_uuids = [];

    // 1. Process remote entities (deleted and modified/synchronized).
    foreach ($remote_entities as $item) {
      $uuid = $item['id'] ?? NULL;
      $attributes = $item['attributes'] ?? [];

      // Add to remote UUIDs index.
      if ($uuid) {
        $remote_uuids[$uuid] = TRUE;
      }

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
      $status = 'synchronized';

      if ($uuid) {
        $nids = \Drupal::entityQuery($entity_type_id)
          ->condition('uuid', $uuid)
          ->accessCheck(TRUE)
          ->range(0, 1)
          ->execute();

        if (empty($nids)) {
          // Exists on remote but not locally = deleted.
          $status = 'deleted';
        }
        else {
          $nid = reset($nids);
          $local_entity = $storage->load($nid);

          if ($local_entity) {
            $local_changed = (int) $local_entity->getChangedTime();
            if ($remote_changed !== NULL && $remote_changed !== $local_changed) {
              $status = 'modified';
            }
          }
        }
      }

      $results[] = [
        'title' => $title,
        'bundle' => $bundle,
        'status' => $status,
        'uuid' => $uuid,
        'entity_type' => $entity_type_id,
      ];
    }

    // 2. Find local entities that don't exist on remote (added).
    // Get base table name.
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);
    $base_table = $entity_type_definition->getBaseTable();
    $uuid_key = $entity_type_definition->getKey('uuid');
    $id_key = $entity_type_definition->getKey('id');

    // Query SQL to get all UUIDs and IDs.
    $connection = \Drupal::database();
    $local_uuids_data = $connection->select($base_table, 'e')
      ->fields('e', [$id_key, $uuid_key])
      ->execute()
      ->fetchAllKeyed(1, 0);

    // Find IDs where UUID not in remote.
    $added_ids = [];
    foreach ($local_uuids_data as $local_uuid => $entity_id) {
      if (!isset($remote_uuids[$local_uuid])) {
        $added_ids[] = $entity_id;
      }
    }

    // Load only the added entities.
    if (!empty($added_ids)) {
      $added_entities = $storage->loadMultiple($added_ids);

      foreach ($added_entities as $local_entity) {
        $results[] = [
          'title' => $local_entity->label(),
          'bundle' => $local_entity->bundle(),
          'status' => 'added',
          'uuid' => $local_entity->uuid(),
          'entity_type' => $entity_type_id,
        ];
      }
    }

    return $results;
  }

  /**
   * Get entity title from attributes based on entity type.
   */
  protected function getEntityTitle(array $attributes, string $entity_type_id): string {

    // Get the entity type definition.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    // Get the label key from entity type definition.
    $label_key = $entity_type->getKey('label') ?? 'title';

    return (string) ($attributes[$label_key] ?? '');
  }

  /**
   * Get entity bundle from remote entity data.
   */
  protected function getEntityBundle(array $remote_entity, string $entity_type_id): string {
    $type = $remote_entity['type'] ?? '';
    return str_replace($entity_type_id . '--', '', $type);
  }

  /**
   * Generate CSV file with results.
   */
  public function generateCsv(array $results): string {
    // Utiliser le système de fichiers privé de Drupal.
    $csv_path = ContentDiffConst::FILE_PATH . '/' . ContentDiffConst::FILE_NAME;

    // S'assurer que le répertoire existe.
    $directory = ContentDiffConst::FILE_PATH;
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    $file = fopen($csv_path, 'w');
    if (!$file) {
      $this->logger->error('Cannot create CSV file: ' . $csv_path);
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
        $row['status'],
      ]);
    }

    fclose($file);

    return $csv_path;
  }

}
