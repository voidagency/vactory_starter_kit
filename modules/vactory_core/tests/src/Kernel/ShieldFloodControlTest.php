<?php

namespace Drupal\Tests\vactory_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shield with flood_control: Basic Auth requests answer, never a 500.
 *
 * Since Shield 1.9, its middleware rate limits failed authentications
 * through the flood service, which flood_control decorates. With
 * flood_control 2.x, that service called a function of flood_control.module,
 * not loaded yet when the middleware runs: every request sending
 * credentials failed with a 500.
 *
 * @group vactory_core
 */
class ShieldFloodControlTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'shield', 'flood_control'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'user', 'shield', 'flood_control']);
    $this->config('shield.settings')
      ->set('shield_enable', TRUE)
      // Kernel tests run from the command line, which Shield skips by
      // default.
      ->set('allow_cli', FALSE)
      ->set('flood_control_enabled', TRUE)
      ->set('credential_provider', 'shield')
      ->set('credentials.shield.user', 'recette')
      ->set('credentials.shield.pass', 'bon-mot-de-passe')
      ->save();
  }

  /**
   * Wrong credentials are refused, valid ones go through.
   */
  public function testBasicAuthWithFloodControl(): void {
    $this->assertSame(401, $this->request('recette', 'faux')->getStatusCode());
    $status = $this->request('recette', 'bon-mot-de-passe')->getStatusCode();
    $this->assertNotSame(401, $status, 'Valid credentials pass Shield.');
    $this->assertLessThan(500, $status, 'No server error with valid credentials.');
  }

  /**
   * Sends a request with Basic Auth credentials through the HTTP kernel.
   */
  private function request(string $user, string $pass) {
    $request = Request::create('/', 'GET', [], [], [], [
      'PHP_AUTH_USER' => $user,
      'PHP_AUTH_PW' => $pass,
      'REMOTE_ADDR' => '127.0.0.1',
    ]);
    return $this->container->get('http_kernel')->handle($request);
  }

}
