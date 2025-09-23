<?php

namespace Drupal\vactory_content_diff\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\vactory_content_diff\ContentDiffConst;
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
      $this->logger = $logger_factory->get('vactory_content_diff');
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
    return [
      'taxonomy_term',
      'node',
    ];
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
      $status = 'synchronized';

      if ($uuid) {
        $nids = \Drupal::entityQuery($entity_type_id)
          ->condition('uuid', $uuid)
          ->accessCheck(TRUE)
          ->range(0, 1)
          ->execute();

        if (empty($nids)) {
          $status = 'new';
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
