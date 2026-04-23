<?php

namespace Drupal\vactory_checklist;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Sends plain-text messages to Slack via an Incoming Webhook.
 */
class SlackNotifier {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a SlackNotifier.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(ClientInterface $http_client, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * Posts a message to Slack.
   *
   * @param string $webhook_url
   *   Slack Incoming Webhook URL.
   * @param string $text
   *   Message body (plain text).
   *
   * @return bool
   *   TRUE on HTTP success, FALSE otherwise.
   */
  public function send(string $webhook_url, string $text): bool {
    if ($webhook_url === '') {
      return FALSE;
    }

    try {
      $response = $this->httpClient->post($webhook_url, [
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'body' => Json::encode(['text' => $text]),
        'timeout' => 10,
      ]);
      $code = $response->getStatusCode();
      if ($code >= 200 && $code < 300) {
        return TRUE;
      }
      $this->logger->warning('Slack webhook returned HTTP @code: @body', [
        '@code' => $code,
        '@body' => (string) $response->getBody(),
      ]);
    }
    catch (GuzzleException $e) {
      $this->logger->error('Slack webhook request failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    return FALSE;
  }

}
