<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Shield with flood_control: Basic Auth requests answer, never a 500.
 *
 * Since Shield 1.9, its middleware rate limits failed authentications
 * through the flood service, which flood_control decorates. With
 * flood_control 2.x, that service called a function of flood_control.module,
 * not loaded yet when the middleware runs: every real request sending
 * credentials failed with a 500. A Kernel test does not show it (modules are
 * all loaded beforehand): the request goes to the site over HTTP.
 *
 * Shield is enabled on a single path only (include method), so that the rest
 * of the site stays reachable even if the test stops before tearDown().
 *
 * @group vactory_core
 */
class ShieldFloodControlTest extends ExistingSiteBase {

  /**
   * The only path protected by Shield during the test.
   */
  private const PATH = '/vactory-shield-flood-test';

  /**
   * The Shield settings before the test, restored afterwards.
   *
   * @var array
   */
  private array $original = [];

  /**
   * Shield user and password, drawn for each run (no fixed credential).
   *
   * @var string[]
   */
  private array $credentials = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $module_handler = \Drupal::moduleHandler();
    if (!$module_handler->moduleExists('shield') || !$module_handler->moduleExists('flood_control')) {
      $this->markTestSkipped('Shield and flood_control are enabled by the profile.');
    }
    $config = \Drupal::configFactory()->getEditable('shield.settings');
    $this->original = $config->getRawData();
    $user = 'test-' . bin2hex(random_bytes(4));
    $this->credentials = [$user, bin2hex(random_bytes(16))];
    $config
      ->set('shield_enable', TRUE)
      ->set('method', 1)
      ->set('paths', self::PATH)
      ->set('allowlist', '')
      // A site may skip Shield on some domains (localhost among them), and
      // the test reaches the site through its base URL.
      ->set('domains', '')
      ->set('flood_control_enabled', TRUE)
      ->set('credential_provider', 'shield')
      ->set('credentials.shield.user', $this->credentials[0])
      ->set('credentials.shield.pass', $this->credentials[1])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->original) {
      \Drupal::configFactory()->getEditable('shield.settings')
        ->setData($this->original)
        ->save();
    }
    // Failed attempts of this test must not count against later requests.
    \Drupal::database()->delete('flood')
      ->condition('event', 'shield.failed_login_ip')
      ->execute();
    parent::tearDown();
  }

  /**
   * Missing and wrong credentials get 401, valid ones go through.
   */
  public function testBasicAuthWithFloodControl(): void {
    $this->assertSame(401, $this->request(NULL), 'No credentials.');
    $this->assertSame(401, $this->request([$this->credentials[0], 'wrong']), 'Wrong password.');
    $status = $this->request($this->credentials);
    $this->assertNotSame(401, $status, 'Valid credentials pass Shield.');
    $this->assertLessThan(500, $status, 'No server error with valid credentials.');
  }

  /**
   * Requests the protected path over HTTP and returns the status code.
   */
  private function request(?array $auth): int {
    $options = ['http_errors' => FALSE, 'allow_redirects' => FALSE];
    if ($auth) {
      $options['auth'] = $auth;
    }
    return \Drupal::httpClient()->get($this->baseUrl . self::PATH, $options)->getStatusCode();
  }

}
