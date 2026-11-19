<?php

namespace Drupal\Tests\vactory_decoupled\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

/**
 * Tests clear_next_nginx_cache().
 *
 * @group vactory_decoupled
 */
class ClearNextNginxCacheTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../vactory_decoupled.module';
    putenv('APP_SKIP_ON_UPDATE_CACHE_CLEAR');
    new Settings(['BASE_FRONTEND_URL' => 'http://front.example']);
  }

  /**
   * Provides the errors thrown by the HTTP client.
   */
  public static function errorProvider(): array {
    $request = new Request('PURGE', 'http://front.example/fr/page');
    return [
      'network error' => [new ConnectException('Could not resolve host', $request)],
      'http error' => [new RequestException('404 Not Found', $request)],
    ];
  }

  /**
   * A failing purge is logged and does not break the caller.
   *
   * @dataProvider errorProvider
   */
  public function testPurgeErrorIsLogged(\Exception $exception): void {
    $client = $this->createMock(ClientInterface::class);
    $client->method('requestAsync')->willThrowException($exception);

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('warning')
      ->with('Next nginx cache cleared for fr/page (no cache found)');
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);

    $container = new ContainerBuilder();
    $container->set('http_client', $client);
    $container->set('logger.factory', $logger_factory);
    \Drupal::setContainer($container);

    $this->assertNull(clear_next_nginx_cache('fr/page'));
  }

}
