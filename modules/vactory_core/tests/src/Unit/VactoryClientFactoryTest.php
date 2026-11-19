<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Core\Http\ClientFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_core\VactoryClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Tests the http_client_factory decorator of vactory_core.
 *
 * @coversDefaultClass \Drupal\vactory_core\VactoryClientFactory
 * @group vactory_core
 */
class VactoryClientFactoryTest extends UnitTestCase {

  /**
   * Builds the decorator and returns the options given to the inner factory.
   *
   * @param mixed $setting
   *   Value of vactory_core.global_config:http_client_ssl_verification.
   * @param array $options
   *   Options passed by the caller.
   *
   * @return array
   *   Options received by the decorated factory.
   */
  protected function optionsReceived($setting, array $options): array {
    $received = NULL;
    $inner = $this->createMock(ClientFactory::class);
    $inner->method('fromOptions')->willReturnCallback(function (array $config) use (&$received) {
      $received = $config;
      return new Client();
    });
    $config_factory = $this->getConfigFactoryStub([
      'vactory_core.global_config' => ['http_client_ssl_verification' => $setting],
    ]);
    $factory = new VactoryClientFactory($inner, HandlerStack::create(), $config_factory);
    $factory->fromOptions($options);
    return $received;
  }

  /**
   * SSL verification is not disabled unless the setting says so.
   *
   * @covers ::fromOptions
   * @dataProvider providerVerify
   */
  public function testVerify($setting, array $options, array $expected): void {
    $this->assertSame($expected, $this->optionsReceived($setting, $options));
  }

  /**
   * Data provider for testVerify().
   */
  public static function providerVerify(): array {
    $caller = ['timeout' => 5, 'headers' => ['Accept' => 'application/json']];
    return [
      'setting missing: caller options kept, no verify override' => [
        NULL,
        $caller,
        $caller,
      ],
      'setting enabled' => [TRUE, $caller, $caller],
      'setting disabled' => [FALSE, $caller, $caller + ['verify' => FALSE]],
      'setting disabled as 0' => [0, [], ['verify' => FALSE]],
      'caller verify wins over disabled setting' => [
        FALSE,
        ['verify' => '/etc/ca.pem'],
        ['verify' => '/etc/ca.pem'],
      ],
    ];
  }

}
