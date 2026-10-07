<?php

namespace Drupal\vactory_webform_typesafe\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vactory_webform_typesafe\Exception\JevApiException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Calls Jev directly over HTTPS, without a Drupal AI provider dependency.
 */
final class TypeSafeClassifier implements TypeSafeClassifierInterface {

  private const ENDPOINT = 'https://api.typesafe.ai/v1/';

  /**
   * Constructs the API client with Drupal's HTTP client and credential store.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ApiKeyStore $keys,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function classify(array $state, array $questions, string $model): array {
    if (trim($model) === '') {
      throw new JevApiException('Select a Jev model in Webform TypeSafe settings.');
    }
    $questions = QuestionDefinition::parse(json_encode($questions, JSON_THROW_ON_ERROR));
    $api_questions = QuestionDefinition::apiQuestions($questions);
    foreach ($api_questions as &$question) {
      // Preserve numeric category names as object keys, never as JSON arrays.
      if ($question['type'] !== 'score' && isset($question['criteria'])) {
        $question['criteria'] = (object) $question['criteria'];
      }
    }
    unset($question);
    $response = $this->request('POST', 'systemone', [
      'state' => $state,
      'model' => $model,
      'questions' => (object) $api_questions,
    ]);
    return JevResponseValidator::validate($response, $questions);
  }

  /**
   * Tests saved credentials without sending any Webform content.
   */
  public function getModels(): array {
    $response = $this->request('GET', 'models', timeout: 10);
    if (!is_array($response['models'] ?? NULL) || !array_is_list($response['models'])) {
      throw new JevApiException('Jev returned an invalid model list.', TRUE);
    }
    $models = [];
    foreach ($response['models'] as $model) {
      if (!is_array($model) || !is_string($model['name'] ?? NULL) || trim($model['name']) === '') {
        throw new JevApiException('Jev returned an invalid model list.', TRUE);
      }
      $models[] = $model['name'];
    }
    return $models;
  }

  /**
   * Makes one bounded request; queue processing owns the retry policy.
   */
  private function request(string $method, string $path, ?array $body = NULL, ?int $timeout = NULL): array {
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->keys->get(),
        'Accept' => 'application/json',
      ],
      'allow_redirects' => FALSE,
      'http_errors' => FALSE,
      'connect_timeout' => 10,
      'timeout' => $timeout ?? max(1, min(180, (int) ($this->configFactory->get('vactory_webform_typesafe.settings')->get('request_timeout') ?? 60))),
    ];
    if ($body !== NULL) {
      $options['headers']['Content-Type'] = 'application/json';
      $options['body'] = json_encode($body, JSON_THROW_ON_ERROR);
    }
    try {
      $response = $this->httpClient->request($method, self::ENDPOINT . $path, $options);
    }
    catch (GuzzleException | \InvalidArgumentException) {
      // Do not chain HTTP exceptions: they can expose headers and request bodies.
      throw new JevApiException('Could not reach Jev. Check connectivity and try again.', TRUE);
    }
    $status = $response->getStatusCode();
    if ($status === 401 || $status === 403) {
      throw new JevApiException('Jev rejected the API key. Check Webform TypeSafe settings and account access.');
    }
    if ($status === 429 || $status === 529) {
      $retry = trim($response->getHeaderLine('Retry-After'));
      $delay = ctype_digit($retry) ? (int) $retry : max(0, (strtotime($retry) ?: 0) - time());
      throw new JevApiException('Jev is busy or rate limited. Processing will retry later.', TRUE, min(86400, $delay));
    }
    if ($status < 200 || $status >= 300) {
      throw new JevApiException('Jev returned HTTP ' . $status . '. Check the model and question settings.', $status >= 500 || $status === 408);
    }
    try {
      // Bound parsing memory even if an upstream service sends a bad response.
      $raw = $response->getBody()->read(2097153);
      if (strlen($raw) > 2097152) {
        throw new JevApiException('Jev returned an oversized response.');
      }
      $decoded = json_decode($raw, TRUE, 64, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException | \RuntimeException $e) {
      if ($e instanceof JevApiException) {
        throw $e;
      }
      throw new JevApiException('Jev returned an unreadable response.', TRUE);
    }
    if (!is_array($decoded)) {
      throw new JevApiException('Jev returned an invalid response.', TRUE);
    }
    return $decoded;
  }

}
