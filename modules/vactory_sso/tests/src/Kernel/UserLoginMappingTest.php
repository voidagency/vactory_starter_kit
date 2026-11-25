<?php

namespace Drupal\Tests\vactory_sso\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oauth_login_oauth2\UserResource;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\vactory_sso\HttpClientMiddleware\UserinfoCaptureMiddleware;
use Drupal\vactory_sso\NestedAttribute;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests the Keycloak attribute mapping done at SSO login.
 *
 * The userinfo call is made by the contrib module itself
 * (UserResource::getResourceOwner()) through the Drupal HTTP client, against
 * a mocked identity provider.
 *
 * @group vactory_sso
 */
class UserLoginMappingTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Userinfo endpoint of the mocked identity provider.
   */
  private const USERINFO = 'https://idp.example.com/realms/test/protocol/openid-connect/userinfo';

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
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // The handler stack is private in core; the test swaps its handler for
    // a mocked identity provider, behind the middlewares of the site.
    if ($container->hasDefinition('http_handler_stack')) {
      $container->getDefinition('http_handler_stack')->setPublic(TRUE);
    }
  }

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
    $this->config('oauth_login_oauth2.settings')
      ->set('miniorange_auth_client_user_info_ep', self::USERINFO)
      ->save();
    $this->setCurrentRoute(UserinfoCaptureMiddleware::CALLBACK_ROUTE);
  }

  /**
   * Makes the given route the current one, as for a routed request.
   */
  private function setCurrentRoute(string $route_name): void {
    $request = Request::create('/mo_callback');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $route_name);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/mo_callback'));
    $this->container->get('request_stack')->push($request);
  }

  /**
   * Makes the Drupal HTTP client answer with the given responses.
   */
  private function mockIdentityProvider(Response ...$responses): void {
    $this->container->get('http_handler_stack')->setHandler(new MockHandler($responses));
  }

  /**
   * Claims of the test user, as returned by Keycloak.
   */
  private function claims(): array {
    return [
      'sub' => 'f3c1',
      'email' => 'jane@example.com',
      'email_verified' => TRUE,
      'attributes' => ['timezone' => ['Africa/Casablanca']],
    ];
  }

  /**
   * Mapped attributes of the userinfo response are copied to the user.
   */
  public function testMappingOnLogin(): void {
    $this->mockIdentityProvider(new Response(200, ['Content-Type' => 'application/json'], json_encode($this->claims())));
    $account = $this->createUser();

    // The contrib module still reads the whole, untouched response.
    $resource_owner = UserResource::getResourceOwner(self::USERINFO, 'access-token');
    $this->assertSame($this->claims(), $resource_owner);

    vactory_sso_user_login($account);

    $user = User::load($account->id());
    $this->assertSame('Africa/Casablanca', $user->getTimeZone());
    $this->assertSame('jane@example.com', $user->getInitialEmail());
    // The claims are used once and not kept.
    $this->assertNull($this->container->get('vactory_sso.userinfo_collector')->get());
    // No write to the configuration of the contrib module any more.
    $this->assertNull($this->config('oauth_login_oauth2.settings')->get('oauth_login_oauth2_free_users'));
  }

  /**
   * Only the userinfo response of the SSO callback is captured.
   *
   * @dataProvider providerIgnoredResponses
   */
  public function testIgnoredResponses(string $route, string $url, int $status, array $body): void {
    $this->setCurrentRoute($route);
    $this->mockIdentityProvider(new Response($status, ['Content-Type' => 'application/json'], json_encode($body)));
    $account = $this->createUser();
    $account->set('init', 'before@example.com')->save();

    $this->container->get('http_client')->request('GET', $url, ['http_errors' => FALSE]);
    $this->assertNull($this->container->get('vactory_sso.userinfo_collector')->get());

    $this->setCurrentRoute(UserinfoCaptureMiddleware::CALLBACK_ROUTE);
    vactory_sso_user_login($account);
    $this->assertSame('before@example.com', User::load($account->id())->getInitialEmail());
  }

  /**
   * Responses that must not be taken as the resource owner.
   */
  public static function providerIgnoredResponses(): array {
    $claims = ['email' => 'jane@example.com'];
    $callback = UserinfoCaptureMiddleware::CALLBACK_ROUTE;
    return [
      'other route' => ['user.login', self::USERINFO, 200, $claims],
      'other URL' => [
        $callback,
        'https://idp.example.com/other',
        200,
        $claims,
      ],
      'error status' => [$callback, self::USERINFO, 401, $claims],
      'error body' => [
        $callback,
        self::USERINFO,
        200,
        ['error' => 'invalid_token'],
      ],
    ];
  }

  /**
   * Without userinfo response the user is left untouched.
   */
  public function testLoginWithoutResourceOwner(): void {
    $account = $this->createUser();
    $account->set('init', 'before@example.com')->save();
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
