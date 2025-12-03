<?php

namespace Drupal\vactory_diff_content\Service;

use Drupal\Core\Entity\EntityStorageInterface;
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
   * JsonApi Path Helper.
   *
   * @var \Drupal\vactory_diff_content\Service\JsonApiPathHelper
   */
  protected $jsonApiPathHelper;

  /**
   * Constructs the service.
   */
  public function __construct(
    ClientInterface $http_client,
    MessengerInterface $messenger,
    $logger_factory,
    TranslationInterface $string_translation,
    EntityTypeManagerInterface $entity_type_manager,
    JsonApiPathHelper $jsonapi_path_helper,
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
    $this->jsonApiPathHelper = $jsonapi_path_helper;
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
    $prefix = $this->jsonApiPathHelper->getPrefix();
    $api_endpoint = $base . $prefix . '/' . $entity_type_id;

    // Get API key from config.
    $config = \Drupal::config('vactory_diff_config_client.settings');
    $api_key = $config->get('remote_api_key');

    $all_entities = [];
    $current_url = $api_endpoint;

    while ($current_url) {
      try {
        // Prepare headers with API key.
        $headers = [
          'Accept' => 'application/vnd.api+json, application/json',
        ];
        if (!empty($api_key)) {
          $headers['apikey'] = $api_key;
        }

        $response = $this->httpClient->request('GET', $current_url, [
          'headers' => $headers,
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
    $remote_uuids = [];

    // Process remote entities (deleted and modified/synchronized).
    foreach ($remote_entities as $item) {
      $result = $this->processRemoteEntity($item, $entity_type_id, $storage);
      $results[] = $result;
      if ($result['uuid']) {
        $remote_uuids[$result['uuid']] = TRUE;
      }
    }

    // Find local entities that don't exist on remote (added).
    $added_results = $this->findAddedEntities($entity_type_id, $remote_uuids, $storage);
    $results = array_merge($results, $added_results);

    return $results;
  }

  /**
   * Process a single remote entity and determine its status.
   *
   * @param array $item
   *   Remote entity item from JSON API.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Entity storage.
   *
   * @return array
   *   Result array with title, bundle, status, uuid, entity_type.
   */
  protected function processRemoteEntity(array $item, string $entity_type_id, EntityStorageInterface $storage): array {
    $uuid = $item['id'] ?? NULL;
    $attributes = $item['attributes'] ?? [];

    $title = $this->getEntityTitle($attributes, $entity_type_id);
    $bundle = $this->getEntityBundle($item, $entity_type_id);
    $remote_changed = $this->parseChangedTimestamp($attributes['changed'] ?? NULL);
    $status = $this->determineEntityStatus($uuid, $entity_type_id, $remote_changed, $storage);

    return [
      'title' => $title,
      'bundle' => $bundle,
      'status' => $status,
      'uuid' => $uuid,
      'entity_type' => $entity_type_id,
    ];
  }

  /**
   * Parse changed timestamp from remote attributes.
   *
   * @param mixed $changed_raw
   *   Raw changed value.
   *
   * @return int|null
   *   Parsed timestamp or NULL.
   */
  protected function parseChangedTimestamp($changed_raw): ?int {
    if ($changed_raw === NULL) {
      return NULL;
    }

    if (is_numeric($changed_raw)) {
      return (int) $changed_raw;
    }

    return strtotime((string) $changed_raw) ?: NULL;
  }

  /**
   * Determine entity status (deleted, modified, or synchronized).
   *
   * @param string|null $uuid
   *   Entity UUID.
   * @param string $entity_type_id
   *   Entity type ID.
   * @param int|null $remote_changed
   *   Remote changed timestamp.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Entity storage.
   *
   * @return string
   *   Status: 'deleted', 'modified', or 'synchronized'.
   */
  protected function determineEntityStatus(?string $uuid, string $entity_type_id, ?int $remote_changed, EntityStorageInterface $storage): string {
    $status = 'synchronized';

    if (!$uuid) {
      return $status;
    }

    $nids = \Drupal::entityQuery($entity_type_id)
      ->condition('uuid', $uuid)
      ->accessCheck(TRUE)
      ->range(0, 1)
      ->execute();

    if (empty($nids)) {
      $status = 'deleted';
    }
    else {
      $nid = reset($nids);
      $local_entity = $storage->load($nid);

      if (!$local_entity) {
        $status = 'deleted';
      }
      else {
        $local_changed = (int) $local_entity->getChangedTime();
        if ($remote_changed !== NULL && $remote_changed !== $local_changed) {
          $status = 'modified';
        }
      }
    }

    return $status;
  }

  /**
   * Find local entities that don't exist on remote (added).
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param array $remote_uuids
   *   Index of remote UUIDs.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Entity storage.
   *
   * @return array
   *   Array of result arrays for added entities.
   */
  protected function findAddedEntities(string $entity_type_id, array $remote_uuids, EntityStorageInterface $storage): array {
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);
    $base_table = $entity_type_definition->getBaseTable();
    $uuid_key = $entity_type_definition->getKey('uuid');
    $id_key = $entity_type_definition->getKey('id');

    $connection = \Drupal::database();
    $local_uuids_data = $connection->select($base_table, 'e')
      ->fields('e', [$id_key, $uuid_key])
      ->execute()
      ->fetchAllKeyed(1, 0);

    $added_ids = [];
    foreach ($local_uuids_data as $local_uuid => $entity_id) {
      if (!isset($remote_uuids[$local_uuid])) {
        $added_ids[] = $entity_id;
      }
    }

    if (empty($added_ids)) {
      return [];
    }

    $added_entities = $storage->loadMultiple($added_ids);
    $results = [];

    foreach ($added_entities as $local_entity) {
      $results[] = [
        'title' => $local_entity->label(),
        'bundle' => $local_entity->bundle(),
        'status' => 'added',
        'uuid' => $local_entity->uuid(),
        'entity_type' => $entity_type_id,
      ];
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
   * Generate the complete content diff report.
   */
  public function generateDiffReport(): array {
    $config = \Drupal::config('vactory_diff_config_client.settings');
    $remote_url = $config->get('remote_url');

    if (empty($remote_url)) {
      return [
        'success' => FALSE,
        'message' => 'Remote URL is required. Please configure it in Vactory Diff Settings (/admin/config/development/vactory-diff/settings).',
      ];
    }

    $content_entity_types = $this->getContentEntityTypes();

    if (empty($content_entity_types)) {
      return [
        'success' => FALSE,
        'message' => 'No content entity types configured. Please configure them in Vactory Diff Settings.',
      ];
    }

    $all_results = [];
    $total_entities = 0;

    foreach ($content_entity_types as $content_entity_type) {
      $this->logger->info('Processing @type entities', ['@type' => $content_entity_type]);

      // Fetch remote entities for this type.
      $remote_entities = $this->fetchRemoteEntities($remote_url, $content_entity_type);

      if (!empty($remote_entities)) {
        $this->logger->info('Fetched @count @type entities', [
          '@count' => count($remote_entities),
          '@type' => $content_entity_type,
        ]);

        // Compare with local entities.
        $compared = $this->compareWithLocal($remote_entities, $content_entity_type);
        $all_results = array_merge($all_results, $compared);
        $total_entities += count($remote_entities);
      }
      else {
        $this->logger->info('No @type entities found', ['@type' => $content_entity_type]);
      }
    }

    // Generate CSV file.
    $csv_path = $this->generateCsv($all_results);

    return [
      'success' => TRUE,
      'message' => 'Diff report generated successfully',
      'csv_path' => $csv_path,
      'total_entities' => $total_entities,
      'results_count' => count($all_results),
      'remote_url' => $remote_url,
    ];
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
