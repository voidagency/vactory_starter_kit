<?php

namespace Drupal\Tests\vactory_decoupled\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_decoupled\Controller\DecoupledOauth2Token;
use Psr\Log\LoggerInterface;

/**
 * Tests the OAuth2 authentication success log.
 *
 * @group vactory_decoupled
 */
class OauthTokenAuthLogTest extends UnitTestCase {

  /**
   * Logs the success when the security review option is enabled.
   */
  public function testSuccessIsLoggedWhenEnabled(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('info')
      ->with($this->stringContains('Session opened for jane'));
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory);
    $container->set('module_handler', $module_handler);
    // Key saved by vactory_security_review's settings form.
    $container->set('config.factory', $this->getConfigFactoryStub([
      'security_review.checks' => ['log_failed_auth' => TRUE],
    ]));
    \Drupal::setContainer($container);

    $controller = (new \ReflectionClass(DecoupledOauth2Token::class))->newInstanceWithoutConstructor();
    (new \ReflectionMethod($controller, 'logAuthSuccess'))->invoke($controller, ['username' => 'jane']);
  }

}
