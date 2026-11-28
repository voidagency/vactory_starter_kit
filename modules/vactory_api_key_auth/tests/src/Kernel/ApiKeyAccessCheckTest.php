<?php

namespace Drupal\Tests\vactory_api_key_auth\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\vactory_api_key_auth\Entity\ApiKey;
use Symfony\Component\HttpFoundation\Request;

/**
 * Characterizes the _route_api_key_auth__access_check requirement.
 *
 * Until 3.2.x, any existing key was accepted, with or without a user: a
 * "system" key. Since 3.3.0 the key must belong to an active account
 * (Redmine #71356). Decision: every key is linked to an account, a technical
 * account per integration (Redmine #71343, #71760).
 *
 * @group vactory_api_key_auth
 */
class ApiKeyAccessCheckTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'vactory_api_key_auth'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Saves a key, linked to the given user ID or to none.
   */
  protected function createKey(?int $uid): string {
    $key = bin2hex(random_bytes(16));
    ApiKey::create([
      'id' => 'test_' . strtolower($this->randomMachineName()),
      'label' => 'Test',
      'key' => $key,
      'user_uuid' => $uid,
    ])->save();
    return $key;
  }

  /**
   * Runs the access check for a request that sends the key.
   */
  protected function allowed(?string $key): bool {
    $request = Request::create('/test');
    if ($key !== NULL) {
      $request->headers->set('apikey', $key);
    }
    $this->container->get('request_stack')->push($request);
    try {
      return $this->container->get('vactory_api_key_auth.access_check')->access()->isAllowed();
    }
    finally {
      $this->container->get('request_stack')->pop();
    }
  }

  /**
   * Which keys open a route that declares the requirement.
   */
  public function testKeys(): void {
    $this->assertTrue($this->allowed($this->createKey((int) $this->createUser()->id())), 'Key of an active account.');

    $blocked = $this->createUser();
    $blocked->block()->save();
    $this->assertFalse($this->allowed($this->createKey((int) $blocked->id())), 'Key of a blocked account.');

    // Behavior changed in 3.3.0: a key without user was accepted before.
    $this->assertFalse($this->allowed($this->createKey(NULL)), 'Key without user.');
    $this->assertFalse($this->allowed($this->createKey(987654)), 'Key of a deleted account.');

    $this->assertFalse($this->allowed('unknown'), 'Unknown key.');
    $this->assertFalse($this->allowed(NULL), 'No key.');
  }

  /**
   * The authentication provider follows the same rule.
   */
  public function testProvider(): void {
    $provider = $this->container->get('authentication.vactory_api_key_auth');
    $authenticate = function (string $key) use ($provider) {
      $request = Request::create('/test');
      $request->headers->set('apikey', $key);
      return $provider->authenticate($request);
    };
    $account = $this->createUser();
    $this->assertEquals($account->id(), $authenticate($this->createKey((int) $account->id()))?->id());
    $this->assertNull($authenticate($this->createKey(NULL)), 'Key without user.');
    $this->assertNull($authenticate($this->createKey(987654)), 'Key of a deleted account.');
  }

}
