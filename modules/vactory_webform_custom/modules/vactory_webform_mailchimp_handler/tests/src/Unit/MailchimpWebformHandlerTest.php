<?php

namespace Drupal\Tests\vactory_webform_mailchimp_handler\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_webform_mailchimp_handler\Plugin\WebformHandler\MailchimpWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

/**
 * Tests the Mailchimp webform handler when Mailchimp cannot be reached.
 *
 * @coversDefaultClass \Drupal\vactory_webform_mailchimp_handler\Plugin\WebformHandler\MailchimpWebformHandler
 * @group vactory_webform_mailchimp_handler
 */
class MailchimpWebformHandlerTest extends UnitTestCase {

  /**
   * Fake Mailchimp "lists" API: every call fails like a network error.
   *
   * @var object
   */
  protected $lists;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->lists = new class() {

      /**
       * Names of the API methods called.
       *
       * @var string[]
       */
      public array $calls = [];

      /**
       * Records the call and fails like an unreachable server.
       */
      public function __call(string $name, array $arguments) {
        $this->calls[] = $name;
        throw new ConnectException('Could not resolve host', new Request('GET', 'https://us1.api.mailchimp.com/3.0/'));
      }

    };
  }

  /**
   * A network error is logged and does not break the submission.
   *
   * @covers ::submitForm
   */
  public function testSubmitWhenMailchimpIsUnreachable(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('error');
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);
    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory);
    \Drupal::setContainer($container);

    $lists = $this->lists;
    $handler = new class($lists) extends MailchimpWebformHandler {

      /**
       * Fake "lists" API.
       *
       * @var object
       */
      protected $fakeLists;

      /**
       * Builds the handler around a fake "lists" API.
       */
      public function __construct($lists) {
        $this->fakeLists = $lists;
      }

      /**
       * {@inheritdoc}
       */
      protected function getClient() {
        return (object) ['lists' => $this->fakeLists];
      }

    };
    $handler->setConfiguration([
      'settings' => [
        'api_key' => 'key-us1',
        'server_prefix' => 'us1',
        'list_id' => 'list-1',
        'email' => 'email',
      ],
    ]);
    $submission = $this->createMock(WebformSubmissionInterface::class);
    $submission->method('getData')->willReturn(['email' => 'visitor@example.com']);

    $form = [];
    $handler->submitForm($form, new FormState(), $submission);

    // Stops after the first failure instead of waiting for each timeout.
    $this->assertSame(['getListMergeFields'], $this->lists->calls);
  }

}
