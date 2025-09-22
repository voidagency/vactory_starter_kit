<?php

namespace Drupal\vactory_content_diff\Controller;

use Drupal\Component\Diff\Diff;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\vactory_content_diff\Service\JsonApiDeserializer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
   * Constructs a ContentDiffCompareController object.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory')
    );
  }

  /**
   * Compares local and remote nodes using JSON API format for both.
   *
   * @param string $bundle
   *   The node bundle.
   * @param string $uuid
   *   The node UUID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with both normalized node data.
   */
  public function compare($bundle, $uuid, Request $request) {
    try {
      // Get remote URL from session.
      $remote_url = "http://php.vactory8.orb.local:8080";
      if (!$remote_url) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Remote URL not configured.',
        ], 400);
      }

      // Get current site base URL for local JSON API call.
      $local_base_url = "http://php.vactory2.orb.local:8080";

      // Fetch both nodes via JSON API with includes for paragraphs.
      $local_data = $this->fetchNodeViaJsonApi($local_base_url, $bundle, $uuid);
      $remote_data = $this->fetchNodeViaJsonApi($remote_url, $bundle, $uuid);
      if (!$local_data) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Local node not found via JSON API.',
        ], 404);
      }

      if (!$remote_data) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Remote node not found via JSON API.',
        ], 404);
      }

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

      $build = [
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

      return $build;

    }
    catch (\Exception $e) {
      $this->loggerFactory->get('vactory_content_diff')->error('Compare error: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Comparison failed: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Fetches node data via JSON API with paragraph includes.
   *
   * @param string $base_url
   *   The base URL (local or remote).
   * @param string $bundle
   *   The node bundle.
   * @param string $uuid
   *   The node UUID.
   *
   * @return array|null
   *   The JSON API response data or NULL on failure.
   */
  protected function fetchNodeViaJsonApi($base_url, $bundle, $uuid) {
    try {
      // Build JSON API URL with includes.
      $url = rtrim($base_url, '/') . "/api/node/{$bundle}/{$uuid}";

      // Add includes for common paragraph and reference fields.
      $includes = [];

      $url .= '?include=' . implode(',', $includes);

      $response = $this->httpClient->request('GET', $url, [
        'timeout' => 30,
        'headers' => [
          'Accept' => 'application/vnd.api+json',
          'Content-Type' => 'application/vnd.api+json',
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);

      // Return the complete response (data + included).
      return [
        'data' => $data['data'] ?? NULL,
        'included' => $data['included'] ?? [],
      ];

    }
    catch (RequestException $e) {
      $this->loggerFactory->get('vactory_content_diff')->error('JSON API fetch error for @url: @message', [
        '@url' => $url ?? 'unknown',
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
