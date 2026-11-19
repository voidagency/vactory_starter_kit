<?php

namespace Drupal\Tests\vactory_diff_content\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_diff_content\Controller\ContentDiffCompareController;
use Drupal\vactory_diff_content\Service\JsonApiPathHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * Tests the remote node fetch when the remote site fails.
 *
 * @coversDefaultClass \Drupal\vactory_diff_content\Controller\ContentDiffCompareController
 * @group vactory_diff_content
 */
class ContentDiffCompareControllerTest extends UnitTestCase {

  /**
   * Provides the remote site failures.
   */
  public static function failureProvider(): array {
    return [
      'network error' => [
        new ConnectException('Could not resolve host: remote.example', new Request('GET', 'https://remote.example')),
        'Could not resolve host',
      ],
      'http error' => [new Response(500), '500 Internal Server Error'],
    ];
  }

  /**
   * A remote failure is logged and reported as "no remote node".
   *
   * @param \Throwable|\Psr\Http\Message\ResponseInterface $failure
   *   What the remote site returns.
   * @param string $error
   *   Expected part of the logged error.
   *
   * @covers ::fetchNodeViaJsonApi
   * @dataProvider failureProvider
   */
  public function testRemoteFailureGivesNoData($failure, string $error): void {
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->getConfigFactoryStub([
      'vactory_diff_config_client.settings' => ['remote_api_key' => 'secret'],
    ]));
    \Drupal::setContainer($container);

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with('JSON API fetch error for @url: @message', $this->callback(
        fn(array $context) => $context['@url'] === 'https://remote.example/jsonapi/node/page/uuid-1'
          && str_contains($context['@message'], $error)
      ));
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);

    $path_helper = $this->createMock(JsonApiPathHelper::class);
    $path_helper->method('buildItemPath')->willReturn('/jsonapi/node/page/uuid-1');
    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $field_manager->method('getFieldDefinitions')->willReturn([]);

    $controller = new ContentDiffCompareController(
      new Client(['handler' => HandlerStack::create(new MockHandler([$failure]))]),
      $logger_factory,
      $this->createMock(EntityTypeManagerInterface::class),
      $field_manager,
      $path_helper
    );
    $fetch = new \ReflectionMethod($controller, 'fetchNodeViaJsonApi');
    $fetch->setAccessible(TRUE);

    $this->assertNull($fetch->invoke($controller, 'https://remote.example/', 'node', 'page', 'uuid-1'));
  }

}
