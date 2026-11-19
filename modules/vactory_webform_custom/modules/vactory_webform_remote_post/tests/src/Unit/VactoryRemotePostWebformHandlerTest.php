<?php

namespace Drupal\Tests\vactory_webform_remote_post\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_webform_remote_post\Plugin\WebformHandler\VactoryRemotePostWebformHandler;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * Tests the remote post handler when the remote server fails.
 *
 * @coversDefaultClass \Drupal\vactory_webform_remote_post\Plugin\WebformHandler\VactoryRemotePostWebformHandler
 * @group vactory_webform_remote_post
 */
class VactoryRemotePostWebformHandlerTest extends UnitTestCase {

  /**
   * The webform message manager mock.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messageManager;

  /**
   * The messenger mock.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * The webform mock.
   *
   * @var \Drupal\webform\WebformInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $webform;

  /**
   * The logger mock.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->messageManager = $this->createMock(WebformMessageManagerInterface::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $link = $this->createMock(Link::class);
    $link->method('toString')->willReturn('');
    $this->webform = $this->createMock(WebformInterface::class);
    $this->webform->method('getSetting')->willReturn(FALSE);
    $this->webform->method('getElement')->willReturn(NULL);
    $this->webform->method('getElementsDecodedAndFlattened')->willReturn([]);
    $this->webform->method('label')->willReturn('Contact');
    $this->webform->method('toLink')->willReturn($link);

    $renderer = $this->createMock(RendererInterface::class);
    $renderer->method('renderPlain')->willReturnCallback(fn(array $build) => $build['#markup']);
    $container = new ContainerBuilder();
    $container->set('renderer', $renderer);
    $container->set('messenger', $this->messenger);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Provides the remote server failures.
   */
  public static function failureProvider(): array {
    return [
      'network error' => [
        new ConnectException('Could not resolve host', new Request('POST', 'https://crm.example/api')),
      ],
      'http error' => [new Response(500, [], 'Server error')],
    ];
  }

  /**
   * A remote failure is logged and shows the default error message.
   *
   * @param \Throwable|\Psr\Http\Message\ResponseInterface $failure
   *   What the remote server returns.
   *
   * @covers ::postSave
   * @dataProvider failureProvider
   */
  public function testRemoteFailureShowsDefaultMessage($failure): void {
    $this->logger->expects($this->once())->method('error');
    $this->messageManager->expects($this->once())
      ->method('display')
      ->with(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE, 'error');
    $this->messenger->expects($this->never())->method('addError');

    $handler = $this->createHandler($failure, '');
    $handler->postSave($this->createSubmission());
  }

  /**
   * A network error shows the custom error message (no response to read).
   *
   * @covers ::postSave
   */
  public function testNetworkErrorShowsCustomMessage(): void {
    $this->logger->expects($this->once())->method('error');
    $this->messageManager->expects($this->never())->method('display');
    $this->messenger->expects($this->once())
      ->method('addError')
      ->with('Service unavailable');

    $failure = new ConnectException('Connection refused', new Request('POST', 'https://crm.example/api'));
    $handler = $this->createHandler($failure, 'Service unavailable');
    $handler->postSave($this->createSubmission());
  }

  /**
   * An invalid URL on a GET handler shows the error instead of crashing.
   *
   * @covers ::postSave
   */
  public function testInvalidGetUrlShowsDefaultMessage(): void {
    $this->logger->expects($this->once())->method('error');
    $this->messageManager->expects($this->once())
      ->method('display')
      ->with(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE, 'error');

    $handler = $this->createHandler(new Response(200), '', [
      'method' => 'GET',
      'completed_url' => 'crm.example/api',
    ]);
    $handler->postSave($this->createSubmission());
  }

  /**
   * Builds the handler around an HTTP client returning the given failure.
   *
   * @param \Throwable|\Psr\Http\Message\ResponseInterface $failure
   *   What the remote server returns.
   * @param string $message
   *   The custom error message configured on the handler.
   * @param array $overrides
   *   Handler configuration overrides.
   */
  protected function createHandler($failure, string $message, array $overrides = []): VactoryRemotePostWebformHandler {
    $http_client = new Client(['handler' => HandlerStack::create(new MockHandler([$failure]))]);

    $token_manager = $this->createMock(WebformTokenManagerInterface::class);
    $token_manager->method('replaceNoRenderContext')->willReturnArgument(0);

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($this->logger);

    $handler = new class($http_client, $token_manager, $logger_factory, $this->messageManager, $this->webform) extends VactoryRemotePostWebformHandler {

      /**
       * Builds the handler without the plugin container.
       */
      public function __construct($http_client, $token_manager, $logger_factory, $message_manager, $webform) {
        $this->httpClient = $http_client;
        $this->tokenManager = $token_manager;
        $this->loggerFactory = $logger_factory;
        $this->messageManager = $message_manager;
        $this->webform = $webform;
        $this->handler_id = 'remote_post';
      }

    };
    $handler->setMessenger($this->messenger);
    $configuration = new \ReflectionProperty($handler, 'configuration');
    $configuration->setAccessible(TRUE);
    $configuration->setValue($handler, [
      'method' => 'POST',
      'type' => 'json',
      'excluded_data' => [],
      'custom_data' => '',
      'custom_options' => '',
      'cast' => FALSE,
      'debug' => FALSE,
      'completed_url' => 'https://crm.example/api',
      'completed_custom_data' => '',
      'message' => $message,
      'messages' => [],
      'error_url' => '',
    ]);
    $configuration->setValue($handler, $overrides + $configuration->getValue($handler));
    return $handler;
  }

  /**
   * Builds a completed submission of the mocked webform.
   */
  protected function createSubmission(): WebformSubmissionInterface {
    $submission = $this->createMock(WebformSubmissionInterface::class);
    $submission->method('getWebform')->willReturn($this->webform);
    $submission->method('getState')->willReturn(WebformSubmissionInterface::STATE_COMPLETED);
    $submission->method('toArray')->willReturn(['data' => ['email' => 'visitor@example.com']]);
    return $submission;
  }

}
