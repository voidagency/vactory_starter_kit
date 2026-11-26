<?php

namespace Drupal\Tests\vactory_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Stage File Proxy (vactory_devtools): files fetched from the origin.
 *
 * Catches a version of the module whose download service changes or no
 * longer writes the file fetched from the origin site. The origin is
 * simulated: no network.
 *
 * @group vactory_core
 */
class StageFileProxyIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'file', 'stage_file_proxy'];

  /**
   * Requests received by the simulated origin.
   *
   * @var array
   */
  private array $requests = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The module writes the download to a temporary file created next to the
    // destination; the virtual file system of Kernel tests cannot create one,
    // a site's public folder can.
    $public = sys_get_temp_dir() . '/vactory-sfp-' . $this->randomMachineName();
    mkdir($public, 0775, TRUE);
    $this->setSetting('file_public_path', $public);
    $this->setSetting('file_temp_path', sys_get_temp_dir());
  }

  /**
   * Replaces the HTTP client by one answering with the given responses.
   */
  private function mockOrigin(array $responses): void {
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(function (callable $handler) {
      return function ($request, array $options) use ($handler) {
        $this->requests[] = (string) $request->getUri();
        return $handler($request, $options);
      };
    });
    $this->container->set('http_client', new Client(['handler' => $stack]));
  }

  /**
   * A missing file is downloaded from the origin and written locally.
   */
  public function testFileIsFetchedFromOrigin(): void {
    $this->mockOrigin([new Response(200, ['Content-Type' => 'image/png'], 'PNG-BYTES')]);
    $fetched = $this->container->get('stage_file_proxy.download_manager')
      ->fetch('https://origin.example.com', 'sites/default/files', 'images/logo.png', []);

    $this->assertTrue($fetched);
    $this->assertSame(['https://origin.example.com/sites/default/files/images/logo.png'], $this->requests);
    $this->assertSame('PNG-BYTES', file_get_contents('public://images/logo.png'));
  }

  /**
   * An origin error is reported without exception, and nothing is written.
   */
  public function testOriginErrorIsHandled(): void {
    $this->mockOrigin([new Response(404)]);
    $fetched = $this->container->get('stage_file_proxy.download_manager')
      ->fetch('https://origin.example.com', 'sites/default/files', 'images/absent.png', []);

    $this->assertFalse($fetched);
    $this->assertFileDoesNotExist('public://images/absent.png');
  }

}
