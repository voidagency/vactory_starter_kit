<?php

namespace Drupal\Tests\vactory_webform_typesafe\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_webform_typesafe\Exception\JevApiException;
use Drupal\vactory_webform_typesafe\Service\ApiKeyStore;
use Drupal\vactory_webform_typesafe\Service\TypeSafeClassifier;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Characterizes TypeSafeClassifier HTTP handling before refactoring.
 *
 * ApiKeyStore is final: a real instance reads the key from Settings.
 *
 * @coversDefaultClass \Drupal\vactory_webform_typesafe\Service\TypeSafeClassifier
 * @group vactory_webform_typesafe
 */
class TypeSafeClassifierTest extends UnitTestCase {

  private const UNREACHABLE = 'Could not reach Jev. Check connectivity and try again.';
  private const BUSY = 'Jev is busy or rate limited. Processing will retry later.';

  /**
   * Recorded HTTP calls: [method, uri, options].
   *
   * @var array
   */
  private array $calls = [];

  /**
   * Settings before the test.
   *
   * @var array
   */
  private array $originalSettings = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    try {
      $this->originalSettings = Settings::getAll();
    }
    catch (\Throwable) {
      $this->originalSettings = [];
    }
    new Settings(['vactory_webform_typesafe_api_key' => 'test-key']);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    new Settings($this->originalSettings);
    parent::tearDown();
  }

  /**
   * Builds the classifier.
   *
   * @param \Psr\Http\Message\ResponseInterface|\Throwable|callable $result
   *   What the HTTP client returns or throws.
   * @param mixed $timeout
   *   The configured request_timeout, or 'never' when config must not be read.
   */
  private function classifier($result, $timeout = 60): TypeSafeClassifier {
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willReturnCallback(function (string $method, string $uri, array $options) use ($result) {
      $this->calls[] = [$method, $uri, $options];
      if ($result instanceof \Throwable) {
        throw $result;
      }
      return is_callable($result) ? $result() : $result;
    });
    $factory = $this->createMock(ConfigFactoryInterface::class);
    if ($timeout === 'never') {
      $factory->expects($this->never())->method('get');
    }
    else {
      $config = $this->createMock(ImmutableConfig::class);
      $config->method('get')->willReturnMap([['request_timeout', $timeout]]);
      $factory->method('get')->willReturnMap([
        ['vactory_webform_typesafe.settings', $config],
      ]);
    }
    $state = $this->createMock(StateInterface::class);
    $state->expects($this->never())->method('get');
    return new TypeSafeClassifier($client, new ApiKeyStore($state), $factory);
  }

  /**
   * Runs a callback and returns the JevApiException it throws.
   */
  private function catchJev(callable $callback): JevApiException {
    try {
      $callback();
    }
    catch (JevApiException $e) {
      $this->assertNull($e->getPrevious());
      return $e;
    }
    $this->fail('Expected a JevApiException.');
  }

  /**
   * Questions mixing numeric choice keys, a score and Noul criteria.
   */
  private static function questions(): array {
    return [
      'n' => [
        'label' => 'N',
        'type' => 'choice',
        'instructions' => 'I',
        'criteria' => ['1' => 'One', '2' => 'Two'],
        'confidence_threshold' => 0.5,
      ],
      's' => [
        'label' => 'S',
        'type' => 'score',
        'instructions' => 'I',
        'criteria' => ['L', 'H'],
      ],
      'b' => [
        'label' => 'B',
        'type' => 'noul',
        'instructions' => 'I',
        'criteria' => ['true' => 'T'],
        'threshold' => 0.5,
      ],
      'e' => [
        'label' => 'E',
        'type' => 'noul',
        'instructions' => 'I',
        'criteria' => [],
      ],
      'x' => ['label' => 'X', 'type' => 'noul', 'instructions' => 'I'],
    ];
  }

  /**
   * A valid provider response for questions().
   */
  private static function validResponse(): array {
    return [
      'model' => 'jev-test',
      'answers' => [
        'n' => [
          'type' => 'choice',
          'choice' => '2',
          'confidence' => 0.6,
          'probabilities' => ['1' => 0.4, '2' => 0.6],
        ],
        's' => [
          'type' => 'score',
          'score' => 1,
          'confidence' => 0.9,
          'probabilities' => [0.1, 0.9],
          'legend' => ['L', 'H'],
        ],
        'b' => ['type' => 'noul', 'noul' => 0.2],
        'e' => ['type' => 'noul', 'noul' => 0.3],
        'x' => ['type' => 'noul', 'noul' => 0.4],
      ],
      'usage' => ['input_tokens' => 3, 'output_tokens' => 4],
    ];
  }

  /**
   * POST options, exact body and validated result.
   *
   * @covers ::classify
   */
  public function testClassifyRequest(): void {
    $classifier = $this->classifier(new Response(200, [], json_encode(self::validResponse())));
    $state = ['message' => ['label' => 'Message', 'value' => 'Hi/there']];
    $result = $classifier->classify($state, self::questions(), 'jev-latest');
    $this->assertCount(1, $this->calls);
    [$method, $uri, $options] = $this->calls[0];
    $this->assertSame('POST', $method);
    $this->assertSame('https://api.typesafe.ai/v1/systemone', $uri);
    $body = '{"state":{"message":{"label":"Message","value":"Hi\/there"}},"model":"jev-latest","questions":{'
      . '"n":{"type":"choice","instructions":"I","criteria":{"1":"One","2":"Two"}},'
      . '"s":{"type":"score","instructions":"I","criteria":["L","H"]},'
      . '"b":{"type":"noul","instructions":"I","criteria":{"true":"T"}},'
      . '"e":{"type":"noul","instructions":"I","criteria":{}},'
      . '"x":{"type":"noul","instructions":"I"}}}';
    $this->assertSame([
      'headers' => [
        'Authorization' => 'Bearer test-key',
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
      ],
      'allow_redirects' => FALSE,
      'http_errors' => FALSE,
      'connect_timeout' => 10,
      'timeout' => 60,
      'body' => $body,
    ], $options);
    $this->assertSame('jev-test', $result['model']);
    $this->assertSame(['input_tokens' => 3, 'output_tokens' => 4], $result['usage']);
    $this->assertSame(['type' => 'noul', 'noul' => 0.2], $result['answers']['b']);
    $this->assertSame('2', $result['answers']['n']['choice']);
  }

  /**
   * An empty state object is sent as a JSON list.
   *
   * @covers ::classify
   */
  public function testClassifyEmptyStateIsList(): void {
    $classifier = $this->classifier(new Response(200, [], json_encode(self::validResponse())));
    $classifier->classify([], self::questions(), 'm');
    $this->assertStringStartsWith('{"state":[],"model":"m",', $this->calls[0][2]['body']);
  }

  /**
   * A blank model fails before any HTTP request or key lookup.
   *
   * @covers ::classify
   */
  public function testClassifyBlankModel(): void {
    new Settings([]);
    $classifier = $this->classifier(new Response(200), 'never');
    $e = $this->catchJev(fn() => $classifier->classify([], self::questions(), " \t"));
    $this->assertSame('Select a Jev model in Webform TypeSafe settings.', $e->getMessage());
    $this->assertFalse($e->retryable);
    $this->assertSame(0, $e->retryAfter);
    $this->assertSame([], $this->calls);
  }

  /**
   * Invalid questions escape as InvalidArgumentException before HTTP.
   *
   * @covers ::classify
   */
  public function testClassifyInvalidQuestions(): void {
    $classifier = $this->classifier(new Response(200), 'never');
    try {
      $classifier->classify([], ['Bad' => []], 'm');
      $this->fail('Expected InvalidArgumentException.');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertSame('Questions need unique machine-name keys and labels.', $e->getMessage());
    }
    $this->assertSame([], $this->calls);
  }

  /**
   * A response failing validation surfaces the validator exception.
   *
   * @covers ::classify
   */
  public function testClassifyInvalidResponse(): void {
    $response = self::validResponse();
    unset($response['answers']['x']);
    $classifier = $this->classifier(new Response(200, [], json_encode($response)));
    $e = $this->catchJev(fn() => $classifier->classify([], self::questions(), 'm'));
    $this->assertSame('Jev returned an invalid classification response.', $e->getMessage());
    $this->assertTrue($e->retryable);
  }

  /**
   * A body that cannot be encoded escapes as JsonException, unwrapped.
   *
   * @covers ::classify
   */
  public function testClassifyBodyJsonExceptionEscapes(): void {
    $classifier = $this->classifier(new Response(200));
    $this->expectException(\JsonException::class);
    try {
      $classifier->classify(['message' => "\xB1\x31"], self::questions(), 'm');
    }
    finally {
      $this->assertSame([], $this->calls);
    }
  }

  /**
   * GET models: explicit 10 s timeout, no body, config never read.
   *
   * @covers ::getModels
   */
  public function testGetModelsRequest(): void {
    $classifier = $this->classifier(new Response(200, [], '{"models":[{"name":"a","x":1},{"name":"b"}]}'), 'never');
    $this->assertSame(['a', 'b'], $classifier->getModels());
    $options = [
      'headers' => [
        'Authorization' => 'Bearer test-key',
        'Accept' => 'application/json',
      ],
      'allow_redirects' => FALSE,
      'http_errors' => FALSE,
      'connect_timeout' => 10,
      'timeout' => 10,
    ];
    $expected = ['GET', 'https://api.typesafe.ai/v1/models', $options];
    $this->assertSame([$expected], $this->calls);
  }

  /**
   * Empty model list is valid.
   *
   * @covers ::getModels
   */
  public function testGetModelsEmpty(): void {
    $this->assertSame([], $this->classifier(new Response(200, [], '{"models":[]}'), 'never')->getModels());
  }

  /**
   * Invalid model lists.
   *
   * @covers ::getModels
   * @dataProvider providerInvalidModels
   */
  public function testGetModelsInvalid(string $body): void {
    $e = $this->catchJev(fn() => $this->classifier(new Response(200, [], $body), 'never')->getModels());
    $this->assertSame('Jev returned an invalid model list.', $e->getMessage());
    $this->assertTrue($e->retryable);
  }

  /**
   * Data provider for testGetModelsInvalid().
   */
  public static function providerInvalidModels(): array {
    return [
      'empty object' => ['{}'],
      'empty list' => ['[]'],
      'models string' => ['{"models":"a"}'],
      'models object' => ['{"models":{"a":{"name":"a"}}}'],
      'item string' => ['{"models":["a"]}'],
      'missing name' => ['{"models":[{"id":"a"}]}'],
      'blank name' => ['{"models":[{"name":" "}]}'],
      'numeric name' => ['{"models":[{"name":1}]}'],
      'second invalid' => ['{"models":[{"name":"a"},{"name":""}]}'],
    ];
  }

  /**
   * Timeout from config: default 60, cast to int, clamped to 1..180.
   *
   * @covers ::classify
   * @dataProvider providerTimeout
   */
  public function testConfiguredTimeout($configured, int $expected): void {
    $classifier = $this->classifier(new Response(200, [], json_encode(self::validResponse())), $configured);
    $classifier->classify([], self::questions(), 'm');
    $this->assertSame($expected, $this->calls[0][2]['timeout']);
  }

  /**
   * Data provider for testConfiguredTimeout().
   */
  public static function providerTimeout(): array {
    return [
      'null' => [NULL, 60],
      'default' => [60, 60],
      'one' => [1, 1],
      'zero' => [0, 1],
      'negative' => [-5, 1],
      'max' => [180, 180],
      'above max' => [181, 180],
      'numeric string' => ['30', 30],
      'float string truncated' => ['2.9', 2],
      'float' => [45.7, 45],
      'non numeric' => ['abc', 1],
      'false' => [FALSE, 1],
    ];
  }

  /**
   * Key store failures escape before config or HTTP are touched.
   *
   * @covers ::getModels
   * @covers ::classify
   */
  public function testKeyStoreExceptionEscapes(): void {
    new Settings(['vactory_webform_typesafe_api_key' => "bad key"]);
    $classifier = $this->classifier(new Response(200), 'never');
    foreach ([
      fn() => $classifier->getModels(),
      fn() => $classifier->classify([], self::questions(), 'm'),
    ] as $callback) {
      $e = $this->catchJev($callback);
      $this->assertSame('The API key contains invalid whitespace or control characters.', $e->getMessage());
      $this->assertFalse($e->retryable);
    }
    $this->assertSame([], $this->calls);
  }

  /**
   * Transport exceptions become one safe retryable error without chaining.
   *
   * @covers ::getModels
   * @dataProvider providerTransportFailures
   */
  public function testTransportFailures(\Throwable $failure): void {
    $e = $this->catchJev(fn() => $this->classifier($failure, 'never')->getModels());
    $this->assertSame(JevApiException::class, get_class($e));
    $this->assertSame(self::UNREACHABLE, $e->getMessage());
    $this->assertTrue($e->retryable);
    $this->assertSame(0, $e->retryAfter);
  }

  /**
   * Data provider for testTransportFailures().
   */
  public static function providerTransportFailures(): array {
    $request = new Request('GET', 'https://api.typesafe.ai/v1/models');
    return [
      'connect' => [new ConnectException('secret', $request)],
      'request' => [new RequestException('secret', $request)],
      'transfer' => [new TransferException('secret')],
      'invalid argument' => [new \InvalidArgumentException('secret')],
    ];
  }

  /**
   * Other client exceptions are not wrapped.
   *
   * @covers ::getModels
   */
  public function testOtherClientExceptionEscapes(): void {
    $classifier = $this->classifier(new \LogicException('raw'), 'never');
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('raw');
    $classifier->getModels();
  }

  /**
   * Non-2xx status handling.
   *
   * @covers ::getModels
   * @dataProvider providerStatus
   */
  public function testStatus(int $status, string $message, bool $retryable): void {
    $response = new Response($status, ['Retry-After' => '30'], 'sensitive-upstream-body');
    $e = $this->catchJev(fn() => $this->classifier($response, 'never')->getModels());
    $this->assertSame($message, $e->getMessage());
    $this->assertSame($retryable, $e->retryable);
    $this->assertSame(in_array($status, [429, 529], TRUE) ? 30 : 0, $e->retryAfter);
  }

  /**
   * Data provider for testStatus().
   */
  public static function providerStatus(): array {
    $key = 'Jev rejected the API key. Check Webform TypeSafe settings and account access.';
    $http = static fn(int $s) => 'Jev returned HTTP ' . $s . '. Check the model and question settings.';
    return [
      '401' => [401, $key, FALSE],
      '403' => [403, $key, FALSE],
      '429' => [429, self::BUSY, TRUE],
      '529' => [529, self::BUSY, TRUE],
      '101' => [101, $http(101), FALSE],
      '199' => [199, $http(199), FALSE],
      '300' => [300, $http(300), FALSE],
      '302' => [302, $http(302), FALSE],
      '304' => [304, $http(304), FALSE],
      '400' => [400, $http(400), FALSE],
      '404' => [404, $http(404), FALSE],
      '407' => [407, $http(407), FALSE],
      '408' => [408, $http(408), TRUE],
      '409' => [409, $http(409), FALSE],
      '422' => [422, $http(422), FALSE],
      '499' => [499, $http(499), FALSE],
      '500' => [500, $http(500), TRUE],
      '502' => [502, $http(502), TRUE],
      // 503 is NOT a rate-limit status here: Retry-After is ignored.
      '503' => [503, $http(503), TRUE],
      '504' => [504, $http(504), TRUE],
      '599' => [599, $http(599), TRUE],
    ];
  }

  /**
   * Retry-After as delta-seconds or HTTP date, clamped to 0..86400.
   *
   * @covers ::getModels
   * @dataProvider providerRetryAfter
   */
  public function testRetryAfter(int $status, ?string $header, int $min, int $max): void {
    $headers = $header === NULL ? [] : ['Retry-After' => $header];
    $e = $this->catchJev(fn() => $this->classifier(new Response($status, $headers), 'never')->getModels());
    $this->assertSame(self::BUSY, $e->getMessage());
    $this->assertTrue($e->retryable);
    $this->assertGreaterThanOrEqual($min, $e->retryAfter);
    $this->assertLessThanOrEqual($max, $e->retryAfter);
  }

  /**
   * Data provider for testRetryAfter().
   */
  public static function providerRetryAfter(): array {
    $date = static fn(int $offset) => gmdate('D, d M Y H:i:s \G\M\T', time() + $offset);
    $cases = [];
    foreach ([429, 529] as $status) {
      $cases += [
        "$status missing" => [$status, NULL, 0, 0],
        "$status empty" => [$status, '', 0, 0],
        "$status zero" => [$status, '0', 0, 0],
        "$status seconds" => [$status, '120', 120, 120],
        "$status padded seconds" => [$status, ' 45 ', 45, 45],
        "$status max seconds" => [$status, '86400', 86400, 86400],
        "$status seconds clamped" => [$status, '999999', 86400, 86400],
        "$status future date" => [$status, $date(120), 115, 120],
        "$status past date" => [$status, $date(-3600), 0, 0],
        "$status far date clamped" => [$status, $date(200000), 86400, 86400],
        "$status garbage" => [$status, 'not a date', 0, 0],
      ];
    }
    return $cases;
  }

  /**
   * Any 2xx body is decoded into an array.
   *
   * @covers ::getModels
   */
  public function testSuccessStatusesDecode(): void {
    foreach ([200, 201, 299] as $status) {
      $classifier = $this->classifier(new Response($status, [], '{"models":[{"name":"m"}]}'), 'never');
      $this->assertSame(['m'], $classifier->getModels());
    }
  }

  /**
   * Unreadable, non-array and oversized bodies.
   *
   * @covers ::getModels
   * @dataProvider providerBodies
   */
  public function testBodies(int $status, string $body, string $message, bool $retryable): void {
    $e = $this->catchJev(fn() => $this->classifier(new Response($status, [], $body), 'never')->getModels());
    $this->assertSame($message, $e->getMessage());
    $this->assertSame($retryable, $e->retryable);
    $this->assertSame(0, $e->retryAfter);
  }

  /**
   * Data provider for testBodies().
   */
  public static function providerBodies(): array {
    $unreadable = 'Jev returned an unreadable response.';
    $invalid = 'Jev returned an invalid response.';
    return [
      'invalid json' => [200, 'invalid JSON', $unreadable, TRUE],
      'empty body' => [200, '', $unreadable, TRUE],
      'no content' => [204, '', $unreadable, TRUE],
      'too deep' => [
        200,
        str_repeat('[', 65) . str_repeat(']', 65),
        $unreadable,
        TRUE,
      ],
      'null' => [200, 'null', $invalid, TRUE],
      'string' => [200, '"x"', $invalid, TRUE],
      'number' => [200, '1', $invalid, TRUE],
      'at size limit' => [
        200,
        '"' . str_repeat('x', 2097150) . '"',
        $invalid,
        TRUE,
      ],
      'oversized' => [
        200,
        str_repeat('x', 2097153),
        'Jev returned an oversized response.',
        FALSE,
      ],
    ];
  }

  /**
   * A stream read failure is reported as unreadable.
   *
   * @covers ::getModels
   */
  public function testStreamFailure(): void {
    $stream = $this->createMock(StreamInterface::class);
    $stream->method('read')->willThrowException(new \RuntimeException('secret'));
    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(200);
    $response->method('getBody')->willReturn($stream);
    $e = $this->catchJev(fn() => $this->classifier($response, 'never')->getModels());
    $this->assertSame('Jev returned an unreadable response.', $e->getMessage());
    $this->assertTrue($e->retryable);
  }

  /**
   * The body is read once with a 2 MiB + 1 byte limit.
   *
   * @covers ::getModels
   */
  public function testBodyReadLimit(): void {
    $stream = $this->createMock(StreamInterface::class);
    $stream->expects($this->once())->method('read')->with(2097153)->willReturn('{"models":[]}');
    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(200);
    $response->method('getBody')->willReturn($stream);
    $this->assertSame([], $this->classifier($response, 'never')->getModels());
  }

}
