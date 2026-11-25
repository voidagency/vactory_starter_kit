<?php

namespace Drupal\Tests\vactory_sso\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Update 10001: SSO return address moved to /mo_callback.
 *
 * @group vactory_sso
 */
class CallbackUriUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'oauth_login_oauth2'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 3) . '/vactory_sso.install';
  }

  /**
   * Returns the stored SSO return address after the update.
   */
  private function runUpdate(string $uri): string {
    $this->config('oauth_login_oauth2.settings')
      ->set('miniorange_auth_client_callback_uri', $uri)
      ->save();
    vactory_sso_update_10001();
    return (string) $this->config('oauth_login_oauth2.settings')->get('miniorange_auth_client_callback_uri');
  }

  /**
   * The 2.x address is moved to /mo_callback.
   */
  public function testLegacyAddressIsMoved(): void {
    $this->assertSame('https://example.com/mo_callback', $this->runUpdate('https://example.com/mo_login'));
    $this->assertSame('https://example.com/mo_callback', $this->runUpdate('https://example.com/mo_login/'));
  }

  /**
   * Other addresses are left as they are.
   */
  public function testOtherAddressesAreKept(): void {
    $this->assertSame('https://example.com/mo_callback', $this->runUpdate('https://example.com/mo_callback'));
    $this->assertSame('', $this->runUpdate(''));
    $this->assertSame('https://example.com/mo_login_custom', $this->runUpdate('https://example.com/mo_login_custom'));
  }

}
