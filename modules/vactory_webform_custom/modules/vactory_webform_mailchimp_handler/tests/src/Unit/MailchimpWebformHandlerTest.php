<?php

namespace Drupal\Tests\vactory_webform_mailchimp_handler\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_webform_mailchimp_handler\Plugin\WebformHandler\MailchimpWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests the Mailchimp webform handler when Mailchimp cannot be reached.
 *
 * @coversDefaultClass \Drupal\vactory_webform_mailchimp_handler\Plugin\WebformHandler\MailchimpWebformHandler
 * @group vactory_webform_mailchimp_handler
 */
class MailchimpWebformHandlerTest extends UnitTestCase {

  /**
   * A network error is logged and does not break the submission.
   *
   * @covers ::submitForm
   */
  public function testSubmitWhenMailchimpIsUnreachable(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->atLeastOnce())->method('error');
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);
    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory);
    \Drupal::setContainer($container);

    $handler = (new \ReflectionClass(MailchimpWebformHandler::class))->newInstanceWithoutConstructor();
    $handler->setConfiguration([
      'settings' => [
        'api_key' => 'key-us1',
        // The ".invalid" TLD never resolves: the client gets a
        // ConnectException.
        'server_prefix' => 'mailchimp.invalid#',
        'list_id' => 'list-1',
        'email' => 'email',
      ],
    ]);
    $submission = $this->createMock(WebformSubmissionInterface::class);
    $submission->method('getData')->willReturn(['email' => 'visitor@example.com']);

    $form = [];
    // mailchimp/marketing 3.0 creates dynamic properties (PHP 8.2
    // deprecations printed on output): not what this test is about.
    $error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);
    try {
      $handler->submitForm($form, new FormState(), $submission);
    }
    finally {
      error_reporting($error_reporting);
    }
  }

}
