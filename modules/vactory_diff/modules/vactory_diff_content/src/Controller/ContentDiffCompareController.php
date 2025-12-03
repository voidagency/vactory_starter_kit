<?php

namespace Drupal\vactory_diff_content\Controller;

use Drupal\Component\Diff\Diff;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\vactory_diff_content\Service\JsonApiPathHelper;
use Drupal\vactory_diff_content\Service\JsonApiDeserializer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Controller for comparing local and remote nodes via JSON API format.
 */
class ContentDiffCompareController extends ControllerBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * JsonApi Path Helper.
   *
   * @var \Drupal\vactory_diff_content\Service\JsonApiPathHelper
   */
  protected $jsonApiPathHelper;

  /**
   * Cache for detected include fields per entity type and bundle.
   *
   * @var array
   */
  protected static $includeFieldsCache = [];

  /**
   * Constructs a ContentDiffCompareController object.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    JsonApiPathHelper $jsonapi_path_helper
  ) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->jsonApiPathHelper = $jsonapi_path_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('vactory_diff_content.jsonapi_path_helper')
    );
  }

  /**
   * Compares local and remote nodes using JSON API format for both.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param string $uuid
   *   The entity UUID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|array
   *   JSON response with error or render array for diff display.
   */
  public function compare($type, $bundle, $uuid, Request $request) {
    try {
      $result = $this->validateAndFetchData($type, $bundle, $uuid);
      if (isset($result['error'])) {
        return $result['error'];
      }

      return $this->buildDiffResponse($result['local_data'], $result['remote_data']);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('vactory_diff_content')->error('Compare error: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Comparison failed: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Validate request and fetch local/remote data.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param string $uuid
   *   The entity UUID.
   *
   * @return array
   *   Array with 'error' (JsonResponse) or 'local_data' and 'remote_data'.
   */
  protected function validateAndFetchData(string $type, string $bundle, string $uuid): array {
    $result = [];
    $error = NULL;

    // Get remote URL from config client settings.
    $config_client_settings = \Drupal::config('vactory_diff_config_client.settings');
    $remote_url = $config_client_settings->get('remote_url');

    if (empty($remote_url)) {
      $error = new JsonResponse([
        'status' => 'error',
        'message' => 'Remote URL not configured. Please configure it in Vactory Diff Settings.',
      ], 400);
    }

    // Fetch local data (only if no error yet).
    if (!$error) {
      $local_data = $this->fetchLocalNodeViaInternalRequest($type, $bundle, $uuid);

      if (!$local_data) {
        $error = new JsonResponse([
          'status' => 'error',
          'message' => 'Local node not found via JSON API.',
        ], 404);
      }
      else {
        $result['local_data'] = $local_data;
      }
    }

    // Fetch remote data (only if no error yet).
    if (!$error) {
      $remote_data = $this->fetchNodeViaJsonApi($remote_url, $type, $bundle, $uuid);

      if (!$remote_data) {
        $error = new JsonResponse([
          'status' => 'error',
          'message' => 'Remote node not found via JSON API.',
        ], 404);
      }
      else {
        $result['remote_data'] = $remote_data;
      }
    }

    // Final return: either an error or the collected result.
    return $error ? ['error' => $error] : $result;
  }

  /**
   * Build diff response from local and remote data.
   *
   * @param array $local_data
   *   Local entity data.
   * @param array $remote_data
   *   Remote entity data.
   *
   * @return array
   *   Render array for diff display.
   */
  protected function buildDiffResponse(array $local_data, array $remote_data): array {
    $deserializer = new JsonApiDeserializer();
    $local_data_deserialized = $deserializer->deserialize($local_data);
    $remote_data_deserialized = $deserializer->deserialize($remote_data);

    $local_yaml = explode("\n", Yaml::encode($local_data_deserialized));
    $remote_yaml = explode("\n", Yaml::encode($remote_data_deserialized));

    $diff = new Diff($local_yaml, $remote_yaml);

    $diff_formatter = \Drupal::service('diff.formatter');
    $diff_formatter->show_header = FALSE;
    $diff_formatter->htmlOutput = TRUE;
    $output = $diff_formatter->format($diff);

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['content-diff-modal']],
      'table' => [
        '#type' => 'table',
        '#header' => [
          ['data' => 'Local', 'colspan' => '2'],
          ['data' => 'remote', 'colspan' => '2'],
        ],
        '#rows' => $output,
        '#attributes' => ['class' => ['content-diff-table', 'diff']],
      ],
      '#attached' => [
        'library' => [
          'system/diff',
        ],
      ],
    ];
  }

  /**
   * Fetches local node data via internal subrequest (no HTTP call).
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The node bundle.
   * @param string $uuid
   *   The node UUID.
   *
   * @return array|null
   *   The JSON API response data or NULL on failure.
   */
  protected function fetchLocalNodeViaInternalRequest($type, $bundle, $uuid) {
    try {
      // Build JSON:API path dynamically using jsonapi_extras config.
      $path = $this->jsonApiPathHelper->buildItemPath($type, $bundle, $uuid);

      // Get include fields for this entity type and bundle (cached).
      $includes = $this->getIncludeFields($type, $bundle);

      // Add includes to the path if any.
      if (!empty($includes)) {
        $path .= '?include=' . implode(',', $includes);
      }

      // Create a subrequest to JSON API.
      $request = Request::create(
        $path,
        'GET',
        [],
        [],
        [],
        [
          'HTTP_ACCEPT' => 'application/vnd.api+json',
          'HTTP_CONTENT_TYPE' => 'application/vnd.api+json',
        ]
      );

      // Get the HTTP kernel and handle the subrequest.
      $kernel = \Drupal::service('http_kernel');
      $response = $kernel->handle($request, HttpKernelInterface::SUB_REQUEST);

      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), TRUE);

        return [
          'data' => $data['data'] ?? NULL,
          'included' => $data['included'] ?? [],
        ];
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('vactory_diff_content')->error('Internal JSON API fetch error for @path: @message', [
        '@path' => "/api/{$type}/{$bundle}/{$uuid}",
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Detects which fields require includes based on their field type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $bundle
   *   The bundle ID.
   *
   * @return array
   *   Array of field names that should be included in JSON API requests.
   */
  protected function getIncludeFields(string $entity_type, string $bundle): array {
    $cache_key = "{$entity_type}:{$bundle}";

    // Check cache first.
    if (isset(static::$includeFieldsCache[$cache_key])) {
      return static::$includeFieldsCache[$cache_key];
    }

    $includes = [];

    try {
      // Get all field definitions for this bundle.
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

      // Field types that typically need includes in JSON API.
      // Note: 'media' and 'taxonomy' are entity types, not field types.
      // They are referenced via entity_reference fields.
      $reference_field_types = [
        'entity_reference',
        'entity_reference_revisions',
      ];

      foreach ($field_definitions as $field_name => $field_definition) {
        // Skip base fields and fields that are not exposed by JSON API.
        if ($field_definition->isComputed() || $field_definition->isReadOnly()) {
          continue;
        }

        $field_type = $field_definition->getType();

        // Check if this field type is in our list of reference types.
        foreach ($reference_field_types as $ref_type) {
          if (strpos($field_type, $ref_type) !== FALSE) {
            $includes[] = $field_name;
            break;
          }
        }
      }

      // Cache the result.
      static::$includeFieldsCache[$cache_key] = $includes;

      // Log for debugging.
      $this->loggerFactory->get('vactory_diff_content')->debug(
        'Detected include fields for @type/@bundle: @fields',
        [
          '@type' => $entity_type,
          '@bundle' => $bundle,
          '@fields' => implode(', ', $includes),
        ]
      );

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('vactory_diff_content')->error(
        'Error detecting include fields for @type/@bundle: @message',
        [
          '@type' => $entity_type,
          '@bundle' => $bundle,
          '@message' => $e->getMessage(),
        ]
      );
    }

    return $includes;
  }

  /**
   * Fetches node data via JSON API with paragraph includes (for remote).
   *
   * @param string $base_url
   *   The base URL (remote).
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The node bundle.
   * @param string $uuid
   *   The node UUID.
   *
   * @return array|null
   *   The JSON API response data or NULL on failure.
   */
  protected function fetchNodeViaJsonApi($base_url, $type, $bundle, $uuid) {
    try {
      // Build JSON:API URL with dynamic resource path.
      $resource_path = $this->jsonApiPathHelper->buildItemPath($type, $bundle, $uuid);
      $url = rtrim($base_url, '/') . $resource_path;

      // Get include fields for this entity type and bundle (cached).
      $includes = $this->getIncludeFields($type, $bundle);

      if (!empty($includes)) {
        $url .= '?include=' . implode(',', $includes);
      }

      // Get API key from config.
      $config = \Drupal::config('vactory_diff_config_client.settings');
      $api_key = $config->get('remote_api_key');

      // Prepare headers with API key.
      $headers = [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
      ];
      if (!empty($api_key)) {
        $headers['apikey'] = $api_key;
      }

      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 30,
        'headers' => $headers,
      ]);

      $data = json_decode($response->getBody(), TRUE);

      // Return the complete response (data + included).
      return [
        'data' => $data['data'] ?? NULL,
        'included' => $data['included'] ?? [],
      ];

    }
    catch (RequestException $e) {
      $this->loggerFactory->get('vactory_diff_content')->error('JSON API fetch error for @url: @message', [
        '@url' => $url ?? 'unknown',
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
