<?php

namespace Drupal\Tests\vactory_api_key_auth\ExistingSite;

use Drupal\Core\Url;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\user\UserInterface;
use Drupal\vactory_api_key_auth\Entity\ApiKey;

/**
 * API key authentication, checked over HTTP.
 *
 * @group vactory_api_key_auth
 */
class ApiKeyAuthTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = [
    'vactory_api_key_auth',
    'vactory_diff',
    'vactory_diff_config_server',
  ];

  /**
   * A route that declares the api_key_auth provider.
   */
  private const KEY_ROUTE = '/api/vactory-diff/config/export';

  /**
   * A route that does not declare it.
   */
  private const ADMIN_ROUTE = '/admin/config';

  /**
   * The key authenticates on its routes only, and only from the header.
   */
  public function testKeyScope(): void {
    $key = $this->createKey($this->createUser([
      'access administration pages',
      'administer site configuration',
      'access vactory diff config export',
    ]));

    $this->assertSame(200, $this->status(self::KEY_ROUTE, $key));
    // Not on a route that does not declare the provider.
    $this->assertSame(403, $this->status(self::ADMIN_ROUTE, $key));
    // Not from the query string.
    $this->assertNotSame(200, $this->status(self::KEY_ROUTE, NULL, ['api_key' => $key]));
    $this->assertSame(403, $this->status(self::ADMIN_ROUTE, NULL, ['api_key' => $key]));
    // Not with a wrong key.
    $this->assertNotSame(200, $this->status(self::KEY_ROUTE, $key . 'x'));
  }

  /**
   * JSON:API still accepts the key (vactory_diff_content reads it so).
   */
  public function testJsonApi(): void {
    $account = $this->createUser(['administer users']);
    $key = $this->createKey($account);
    $url = Url::fromRoute('jsonapi.user--user.individual', ['entity' => $account->uuid()])->toString();

    $this->status($url, $key);
    $this->assertSession()->statusCodeEquals(200);
    // The email address is only shown to its owner or to user administrators.
    $this->assertSession()->responseContains($account->getEmail());

    $this->status($url, NULL);
    $this->assertSession()->responseNotContains($account->getEmail());
  }

  /**
   * The key of a blocked account is refused.
   */
  public function testBlockedAccount(): void {
    $account = $this->createUser(['access vactory diff config export']);
    $key = $this->createKey($account);
    $this->assertSame(200, $this->status(self::KEY_ROUTE, $key));

    $account->block()->save();
    $this->assertNotSame(200, $this->status(self::KEY_ROUTE, $key));
  }

  /**
   * Creates an API key for the account.
   */
  private function createKey(UserInterface $account): string {
    $key = bin2hex(random_bytes(16));
    $api_key = ApiKey::create([
      'id' => 'test_' . $this->randomMachineName(),
      'label' => 'Test',
      'key' => $key,
      'user_uuid' => $account->id(),
    ]);
    $api_key->save();
    $this->markEntityForCleanup($api_key);
    return $key;
  }

  /**
   * Returns the HTTP status of an anonymous GET, with the key in the header.
   */
  private function status(string $path, ?string $key, array $query = []): int {
    $this->getSession()->setRequestHeader('apikey', $key ?? '');
    $this->drupalGet(str_starts_with($path, $this->baseUrl) ? $path : $this->baseUrl . $path, ['query' => $query]);
    $this->getSession()->setRequestHeader('apikey', '');
    return $this->getSession()->getStatusCode();
  }

}
