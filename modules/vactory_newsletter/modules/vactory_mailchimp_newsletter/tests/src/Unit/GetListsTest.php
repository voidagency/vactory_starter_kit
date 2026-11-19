<?php

namespace Drupal\Tests\vactory_mailchimp_newsletter\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests get_lists() when Mailchimp cannot be reached.
 *
 * @group vactory_mailchimp_newsletter
 */
class GetListsTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../vactory_mailchimp_newsletter.module';
  }

  /**
   * A network error is logged and gives an empty list instead of a crash.
   */
  public function testUnreachableMailchimpGivesNoLists(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with($this->callback(fn($message) => str_contains((string) $message, 'An exception occurred while getting Mailchimp lists')));
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);
    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // The client builds its URL as "https://<server>.api.mailchimp.com": this
    // server prefix points it to a closed local port, so the real Mailchimp
    // client fails at once with a Guzzle ConnectException (connection
    // refused), as on a DNS failure or a timeout. The client library also
    // creates dynamic properties (deprecated since PHP 8.2): keep that noise
    // out of the test output.
    $error_level = error_reporting(E_ALL & ~E_DEPRECATED);
    try {
      $lists = get_lists([
        'api_key' => 'key-us1',
        'server_prefix' => '127.0.0.1:1/',
      ]);
    }
    finally {
      error_reporting($error_level);
    }

    $this->assertSame([], $lists);
  }

}
