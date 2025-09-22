<?php

namespace Drupal\vactory_content_diff\Service;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\vactory_content_diff\ContentDiffStatus;

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
   * Constructs the service.
   */
  public function __construct(ClientInterface $http_client, MessengerInterface $messenger, $logger_factory, TranslationInterface $string_translation) {
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
  }

  /**
   * Fetches remote nodes via JSON:API Cross Bundles endpoint.
   *
   * @param string $url
   *   Base URL of the remote instance (e.g., https://remote.tld).
   *
   * @return array
   *   Decoded JSON:API data array with 'data' key, empty on failure.
   */
  public function fetchRemoteContent(string $url): array {
    $base = rtrim($url, '/');
    $endpoint = $base . '/api/node';
    try {
      $this->messenger->addStatus($this->t('Fetching remote content from @url ...', ['@url' => $endpoint]));
      $response = $this->httpClient->request('GET', $endpoint, [
        'headers' => [
          'Accept' => 'application/vnd.api+json, application/json',
        ],
        'timeout' => 20,
      ]);
      $status = $response->getStatusCode();
      if ($status !== 200) {
        $this->logger->error('Remote fetch failed with HTTP @code for @url', [
          '@code' => $status,
          '@url' => $endpoint,
        ]);
        $this->messenger->addError($this->t('Remote fetch failed with HTTP @code.', ['@code' => $status]));
        return [];
      }
      $json = (string) $response->getBody();
      $decoded = json_decode($json, TRUE);
      if (!is_array($decoded)) {
        $this->logger->error('Invalid JSON response from @url', ['@url' => $endpoint]);
        $this->messenger->addError($this->t('Invalid JSON response from remote endpoint.'));
        return [];
      }
      return $decoded;
    }
    catch (GuzzleException $e) {
      $this->logger->error('HTTP error fetching remote content: @message', ['@message' => $e->getMessage()]);
      $this->messenger->addError($this->t('HTTP error fetching remote content.'));
    }
    catch (\Throwable $e) {
      $this->logger->error('Unexpected error: @message', ['@message' => $e->getMessage()]);
      $this->messenger->addError($this->t('Unexpected error occurred fetching remote content.'));
    }
    return [];
  }

  /**
   * Handles pagination for the JSON:API endpoint and aggregates all nodes.
   *
   * JSON:API Cross Bundles usually returns pagination links under 'links.next'.
   *
   * @param string $url
   *   Base URL of the remote instance.
   *
   * @return array
   *   Merged array of remote node resource objects.
   */
  public function handlePagination(string $url): array {
    $all = [];
    $current = $this->fetchRemoteContent($url);
    if (empty($current)) {
      return [];
    }
    $page = 1;
    while (TRUE) {
      if (!empty($current['data']) && is_array($current['data'])) {
        $all = array_merge($all, $current['data']);
      }
      // Progress message.
      $this->messenger->addStatus($this->t('Fetched page @num, total items: @count', [
        '@num' => $page,
        '@count' => count($all),
      ]));
      $page++;

      $next = $current['links']['next']['href'] ?? NULL;
      if (!$next) {
        break;
      }

      try {
        $response = $this->httpClient->request('GET', $next, [
          'headers' => [
            'Accept' => 'application/vnd.api+json, application/json',
          ],
          'timeout' => 20,
        ]);
        if ($response->getStatusCode() !== 200) {
          $this->logger->warning('Pagination fetch failed with HTTP @code for @url', [
            '@code' => $response->getStatusCode(),
            '@url' => $next,
          ]);
          break;
        }
        $json = (string) $response->getBody();
        $current = json_decode($json, TRUE) ?: [];
      }
      catch (GuzzleException $e) {
        $this->logger->error('HTTP error during pagination: @message', ['@message' => $e->getMessage()]);
        break;
      }
      catch (\Throwable $e) {
        $this->logger->error('Unexpected error during pagination: @message', ['@message' => $e->getMessage()]);
        break;
      }
    }

    return $all;
  }

  /**
   * Compares remote nodes with local by UUID and changed timestamp.
   *
   * @param array $remote_nodes
   *   Remote node resource objects (from JSON:API).
   *
   * @return array
   *   Array of rows: [title, bundle, status_key, status, status_class].
   */
  public function compareWithLocal(array $remote_nodes): array {
    $results = [];
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    foreach ($remote_nodes as $item) {
      $uuid = $item['id'] ?? NULL;
      $attributes = $item['attributes'] ?? [];
      $title = $attributes['title'] ?? '';
      $bundle = $item['type'] ? str_replace('node--', '', $item['type']) : '';

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
        $nids = \Drupal::entityQuery('node')->condition('uuid', $uuid)->accessCheck(TRUE)->range(0, 1)->execute();
        if (empty($nids)) {
          $status = ContentDiffStatus::NEW_ENTITY;
        }
        else {
          $nid = reset($nids);
          $node = $storage->load($nid);
          if ($node) {
            $local_changed = (int) $node->getChangedTime();
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
      ];
    }
    return $results;
  }

}
