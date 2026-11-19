<?php

namespace Drupal\Tests\vactory_sso\Kernel;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\vactory_sso\NestedAttribute;

/**
 * Tests the Keycloak attribute mapping done at SSO login.
 *
 * @group vactory_sso
 */
class UserLoginMappingTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'oauth_login_oauth2',
    'vactory_sso',
  ];

  /**
   * {@inheritdoc}
   *
   * The user save during login runs vactory_sso_user_presave(), which writes
   * oauth_login_oauth2.settings:oauth_login_oauth2_free_users, a key the
   * contrib module does not declare in its schema.
   */
  protected static $configSchemaCheckerExclusions = ['oauth_login_oauth2.settings'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The module lives in the profile: its config schema is only discovered
    // when the profile is the install profile.
    $this->setInstallProfile('vactory_starter_kit');
    $this->installEntitySchema('user');
    $this->config('vactory_sso.settings')
      ->set('max_attr_count', 2)
      ->set('vactory_sso_drupal_user_attr_1', 'timezone')
      ->set('vactory_sso_keycloak_user_attr_1', 'attributes.timezone')
      ->set('vactory_sso_drupal_user_attr_2', 'init')
      ->set('vactory_sso_keycloak_user_attr_2', 'email')
      ->save();
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn('oauth_login_oauth2.mo_login');
    $this->container->set('current_route_match', $route_match);
  }

  /**
   * Mapped Keycloak attributes are copied to the Drupal user.
   */
  public function testMappingOnLogin(): void {
    $account = $this->createUser();
    $_SESSION['resource_owner'] = [
      'email' => 'jane@example.com',
      'attributes' => ['timezone' => ['Africa/Casablanca']],
    ];
    try {
      vactory_sso_user_login($account);
    }
    finally {
      unset($_SESSION['resource_owner']);
    }

    $user = User::load($account->id());
    $this->assertSame('Africa/Casablanca', $user->getTimeZone());
    $this->assertSame('jane@example.com', $user->getInitialEmail());
  }

  /**
   * Without resource owner in session the user is left untouched.
   */
  public function testLoginWithoutResourceOwner(): void {
    $account = $this->createUser();
    $account->set('init', 'before@example.com')->save();
    unset($_SESSION['resource_owner']);
    vactory_sso_user_login($account);
    $this->assertSame('before@example.com', User::load($account->id())->getInitialEmail());
  }

  /**
   * Nested attribute lookup rules.
   */
  public function testNestedAttribute(): void {
    $resource = [
      'a' => ['b' => 'x', 'list' => ['first', 'second']],
      'zero' => [0],
    ];
    $this->assertSame('x', NestedAttribute::get($resource, 'a.b'));
    $this->assertSame('first', NestedAttribute::get($resource, 'a.list'));
    $this->assertSame(0, NestedAttribute::get($resource, 'zero'));
    $this->assertNull(NestedAttribute::get($resource, 'a'), 'Associative value.');
    $this->assertNull(NestedAttribute::get(['empty' => []], 'empty'));
    $this->assertFalse(NestedAttribute::get(['flag' => FALSE], 'flag'));
    $this->assertSame('', NestedAttribute::get(['blank' => ''], 'blank'));
    $this->assertNull(NestedAttribute::get($resource, 'a.missing'));
    $this->assertNull(NestedAttribute::get($resource, ''));
    $this->assertNull(NestedAttribute::get($resource, 'a.b.c'), 'Walks through a scalar.');
  }

}
