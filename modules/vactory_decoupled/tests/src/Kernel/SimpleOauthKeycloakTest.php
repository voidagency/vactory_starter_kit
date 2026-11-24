<?php

namespace Drupal\Tests\vactory_decoupled\Kernel;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\vactory_decoupled\SimpleOauthAuthenticationProvider;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Lcobucci\JWT\Token\DataSet;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Characterization of the Keycloak path of the Simple OAuth provider.
 *
 * Tokens are signed with RSA keys generated for the test; the JWKS endpoint
 * is a mocked HTTP client. Fixture hosts and realms are fictitious.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled\SimpleOauthAuthenticationProvider
 * @group vactory_decoupled
 */
class SimpleOauthKeycloakTest extends KernelTestBase {

  /**
   * Fictitious realm issuer.
   */
  const ISSUER = 'https://sso.example.test/realms/test-realm';

  /**
   * Default JWKS URI of the realm.
   */
  const CERTS = self::ISSUER . '/protocol/openid-connect/certs';

  /**
   * Cache bin of the provider.
   */
  const BIN = 'vactory_decoupled_oauth';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * RSA key pairs, keyed by name: ['private' => PEM, 'jwk' => array].
   *
   * @var array
   */
  protected static $keys = [];

  /**
   * Queued JWKS responses.
   *
   * @var \GuzzleHttp\Handler\MockHandler
   */
  protected $mockHandler;

  /**
   * HTTP requests sent (Guzzle history).
   *
   * @var array
   */
  protected $history = [];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Services fetched by authenticate().
    $container->register('cache.vactory_decoupled_oauth', MemoryBackend::class)
      ->addArgument(new Reference('datetime.time'));
    $container->register('vactory_decoupled.user_manager', \stdClass::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Neither module is installed: register their namespaces.
    $module_list = $this->container->get('extension.list.module');
    foreach (['simple_oauth', 'vactory_decoupled'] as $module) {
      $path = $this->root . '/' . $module_list->getPath($module) . '/src';
      $this->classLoader->addPsr4('Drupal\\' . $module . '\\', $path);
    }

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    // The super admin, uid 1.
    $this->createAccount('admin', 'admin@example.test');

    $this->mockHandler = new MockHandler();
    $stack = HandlerStack::create($this->mockHandler);
    $stack->push(Middleware::history($this->history));
    $this->container->set('http_client', new Client(['handler' => $stack]));

    $this->setSetting('KEYCLOAK_ISSUER', self::ISSUER);
    JWT::$leeway = 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    JWT::$leeway = 0;
    parent::tearDown();
  }

  /**
   * Returns (and generates once) a named RSA key pair.
   */
  protected static function key(string $name): array {
    if (!isset(static::$keys[$name])) {
      $resource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
      ]);
      openssl_pkey_export($resource, $private);
      $details = openssl_pkey_get_details($resource);
      static::$keys[$name] = [
        'private' => $private,
        'jwk' => [
          'kty' => 'RSA',
          'n' => static::b64($details['rsa']['n']),
          'e' => static::b64($details['rsa']['e']),
        ],
      ];
    }
    return static::$keys[$name];
  }

  /**
   * Base64url encoding.
   */
  protected static function b64(string $value): string {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
  }

  /**
   * A JWKS entry for a key.
   */
  protected function jwk(string $name, string $kid, array $extra = []): array {
    $defaults = ['kid' => $kid, 'alg' => 'RS256', 'use' => 'sig'];
    return $extra + static::key($name)['jwk'] + $defaults;
  }

  /**
   * The JWKS entry of the main realm key.
   */
  protected function main(): array {
    return $this->jwk('main', 'kid-main');
  }

  /**
   * Creates a user.
   */
  protected function createAccount(string $name, string $mail, int $status = 1) {
    $user = User::create(['name' => $name, 'mail' => $mail, 'status' => $status]);
    $user->save();
    return $user;
  }

  /**
   * A token for a username and email (NULL removes the email claim).
   */
  protected function tokenFor(string $username, ?string $email, array $claims = []): string {
    $claims += ['preferred_username' => $username, 'email' => $email];
    return $this->token($claims);
  }

  /**
   * Asserts a token authenticates the named account.
   */
  protected function assertAuthenticated(string $name, string $jwt): void {
    $this->assertSame($name, $this->authenticate($jwt)->getAccountName());
  }

  /**
   * The cached item of the provider for a cache id.
   */
  protected function cached(string $cid) {
    return \Drupal::cache(self::BIN)->get($cid);
  }

  /**
   * Queues a JWKS response.
   */
  protected function queueJwks(array $keys): void {
    $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode(['keys' => $keys])));
  }

  /**
   * Signs a token.
   *
   * @param array $claims
   *   Claims merged over valid defaults (NULL removes a claim).
   * @param string $key
   *   Key pair name.
   * @param string $kid
   *   Header "kid".
   */
  protected function token(array $claims = [], string $key = 'main', string $kid = 'kid-main'): string {
    $now = time();
    $payload = array_filter($claims + [
      'iss' => self::ISSUER,
      'iat' => $now,
      'exp' => $now + 300,
      'aud' => 'account',
      'azp' => 'frontend-client',
      'preferred_username' => 'jdoe',
      'email' => 'jdoe@example.test',
      'email_verified' => TRUE,
    ], function ($value) {
      return $value !== NULL;
    });
    return JWT::encode($payload, static::key($key)['private'], 'RS256', $kid);
  }

  /**
   * Builds the provider without the simple_oauth dependencies.
   */
  protected function provider(): SimpleOauthAuthenticationProvider {
    return (new \ReflectionClass(SimpleOauthAuthenticationProvider::class))->newInstanceWithoutConstructor();
  }

  /**
   * Authenticates a bearer token with the keycloak provider header.
   */
  protected function authenticate(string $jwt) {
    $request = Request::create('/api/test');
    $request->headers->set('X-Auth-Provider', 'keycloak');
    $request->headers->set('Authorization', 'Bearer ' . $jwt);
    return $this->provider()->authenticate($request);
  }

  /**
   * Asserts authentication fails with the exact exception.
   */
  protected function assertRefused(string $jwt, string $message = 'Access token could not be verified'): void {
    try {
      $this->authenticate($jwt);
      $this->fail('UnauthorizedHttpException expected.');
    }
    catch (UnauthorizedHttpException $e) {
      $this->assertSame(UnauthorizedHttpException::class, get_class($e));
      $this->assertSame($message, $e->getMessage());
      $this->assertSame(401, $e->getStatusCode());
      $this->assertSame(['WWW-Authenticate' => 'Bearer realm="keycloak"'], $e->getHeaders());
      $this->assertNull($e->getPrevious());
    }
  }

  /**
   * Number of users.
   */
  protected function userCount(): int {
    return (int) \Drupal::entityQuery('user')->accessCheck(FALSE)->count()->execute();
  }

  /**
   * Keycloak is refused when no issuer is configured.
   *
   * @covers ::authenticate
   */
  public function testIssuerNotConfigured(): void {
    foreach ([NULL, '', '/'] as $issuer) {
      $this->setSetting('KEYCLOAK_ISSUER', $issuer);
      $this->assertRefused($this->token(), 'Keycloak authentication is not configured');
    }
    $this->assertCount(0, $this->history, 'No JWKS request.');
  }

  /**
   * A valid token logs in the user with the same name; JWKS cached.
   *
   * @covers ::authenticate
   */
  public function testUserLoadedByName(): void {
    // Trailing slash of the configured issuer is ignored.
    $this->setSetting('KEYCLOAK_ISSUER', self::ISSUER . '/');
    $user = $this->createAccount('jdoe', 'other@example.test');
    // An encryption key is published too.
    $encryption = $this->jwk('other', 'kid-enc', ['use' => 'enc']);
    $this->queueJwks([$encryption, $this->main()]);
    $count = $this->userCount();

    $account = $this->authenticate($this->token());
    $this->assertSame((int) $user->id(), (int) $account->id());
    $this->assertSame($count, $this->userCount());

    $this->assertCount(1, $this->history);
    $request = $this->history[0]['request'];
    $this->assertSame('GET', $request->getMethod());
    $this->assertSame(self::CERTS, (string) $request->getUri());
    $this->assertSame(5, $this->history[0]['options']['timeout']);

    // Cached JWKS (encryption keys filtered out).
    $cached = $this->cached('keycloak_jwks:' . self::CERTS);
    $this->assertSame([$this->main()], $cached->data['keys']);
    $this->assertEqualsWithDelta(time() + 3600, (int) $cached->expire, 120);

    // Second call: no new JWKS request.
    $account = $this->authenticate($this->token());
    $this->assertSame((int) $user->id(), (int) $account->id());
    $this->assertCount(1, $this->history);
    $this->assertSame(30, JWT::$leeway, 'The provider sets a 30s leeway.');
  }

  /**
   * KEYCLOAK_JWKS_URI overrides the JWKS location.
   *
   * @covers ::authenticate
   */
  public function testJwksUriSetting(): void {
    $this->setSetting('KEYCLOAK_JWKS_URI', 'https://keys.example.test/jwks.json');
    $this->queueJwks([$this->main()]);
    $this->authenticate($this->token());
    $uri = (string) $this->history[0]['request']->getUri();
    $this->assertSame('https://keys.example.test/jwks.json', $uri);
  }

  /**
   * Invalid JWKS answers.
   *
   * @covers ::authenticate
   */
  public function testInvalidJwks(): void {
    // No keys.
    $this->queueJwks([]);
    $this->assertRefused($this->token());
    // HTTP error.
    $this->mockHandler->append(new Response(500));
    $this->assertRefused($this->token());
    // Not JSON.
    $this->mockHandler->append(new Response(200, [], 'not json'));
    $this->assertRefused($this->token());
    $this->assertCount(3, $this->history, 'These answers are not cached.');

    // Only encryption keys: the filtered (empty) key set IS cached, later
    // calls fail without a new request until the cache expires.
    $this->queueJwks([$this->jwk('main', 'kid-main', ['use' => 'enc'])]);
    $this->assertRefused($this->token());
    $this->assertCount(4, $this->history);
    $cached = $this->cached('keycloak_jwks:' . self::CERTS);
    $this->assertSame(['keys' => []], $cached->data);
    $this->queueJwks([$this->main()]);
    $this->assertRefused($this->token());
    $this->assertCount(4, $this->history);
  }

  /**
   * Bad signatures, algorithms and time claims.
   *
   * @covers ::authenticate
   */
  public function testInvalidTokens(): void {
    $this->queueJwks([$this->main()]);
    // Signed by another key announcing the realm "kid".
    $this->assertRefused($this->token([], 'other', 'kid-main'));
    // Tampered payload.
    [$header, , $signature] = explode('.', $this->token());
    $forged = ['iss' => self::ISSUER, 'preferred_username' => 'admin'];
    $payload = static::b64(json_encode($forged));
    $this->assertRefused("$header.$payload.$signature");
    // HS256 signed with the public modulus (key confusion).
    $hs256 = JWT::encode($forged, $this->main()['n'], 'HS256', 'kid-main');
    $this->assertRefused($hs256);
    // Algorithm "none".
    $none = ['alg' => 'none', 'typ' => 'JWT', 'kid' => 'kid-main'];
    $none = static::b64(json_encode($none));
    $this->assertRefused("$none.$payload.");
    // Garbage.
    $this->assertRefused('not-a-jwt');
    $this->assertRefused('');
    // Expired beyond the 30s leeway.
    $this->assertRefused($this->token(['exp' => time() - 60]));
    // Not yet valid beyond the leeway.
    $this->assertRefused($this->token(['nbf' => time() + 120]));
    $this->assertRefused($this->token(['iat' => time() + 120]));
    $this->assertCount(1, $this->history);
    $this->assertSame(1, $this->userCount(), 'No user created.');

    // Within the leeway: accepted.
    $this->assertAuthenticated('jdoe', $this->token(['exp' => time() - 10]));
  }

  /**
   * Issuer claim must match exactly.
   *
   * @covers ::authenticate
   */
  public function testWrongIssuer(): void {
    $this->queueJwks([$this->main()]);
    $this->assertRefused($this->token(['iss' => 'https://sso.example.test/realms/other']));
    $this->assertRefused($this->token(['iss' => self::ISSUER . '/']));
    $this->assertRefused($this->token(['iss' => NULL]));
    $this->assertSame(1, $this->userCount());
  }

  /**
   * KEYCLOAK_AUDIENCE: "aud" (string or list) or "azp".
   *
   * @covers ::authenticate
   */
  public function testAudience(): void {
    $this->queueJwks([$this->main()]);
    // Not configured: any audience.
    $audience = function ($aud, $azp) {
      return $this->token(['aud' => $aud, 'azp' => $azp]);
    };
    $this->assertAuthenticated('jdoe', $audience('anything', NULL));

    $this->setSetting('KEYCLOAK_AUDIENCE', 'my-client');
    $this->assertRefused($audience('account', 'frontend-client'));
    $this->assertRefused($audience(NULL, NULL));
    $this->assertRefused($audience(['account', 'other'], NULL));
    $this->assertAuthenticated('jdoe', $audience('my-client', NULL));
    $this->assertAuthenticated('jdoe', $audience(['account', 'my-client'], NULL));
    $this->assertAuthenticated('jdoe', $audience('account', 'my-client'));
  }

  /**
   * The preferred_username claim is required.
   *
   * @covers ::authenticate
   */
  public function testMissingPreferredUsername(): void {
    $this->queueJwks([$this->main()]);
    $this->assertRefused($this->token(['preferred_username' => NULL]));
    $this->assertRefused($this->token(['preferred_username' => '']));
    $this->assertRefused($this->token(['preferred_username' => 123]));
    $this->assertRefused($this->token(['preferred_username' => ['jdoe']]));
    $this->assertSame(1, $this->userCount());
  }

  /**
   * Account linking by email only when Keycloak verified it.
   *
   * @covers ::authenticate
   */
  public function testUserLoadedByVerifiedEmail(): void {
    $existing = $this->createAccount('legacy-name', 'jdoe@example.test');
    $this->queueJwks([$this->main()]);

    // Verified email: the existing account.
    $this->assertSame((int) $existing->id(), (int) $this->authenticate($this->token())->id());
    $this->assertSame(2, $this->userCount());

    // Not verified: a new account is created with the same email.
    foreach ([FALSE, NULL] as $verified) {
      $username = 'unverified' . ($verified === NULL ? '-missing' : '');
      $claims = ['email_verified' => $verified];
      $account = $this->authenticate($this->tokenFor($username, 'jdoe@example.test', $claims));
      $this->assertNotSame((int) $existing->id(), (int) $account->id());
      $this->assertSame($username, $account->getAccountName());
      $this->assertSame('jdoe@example.test', $account->getEmail());
    }
    $this->assertSame(4, $this->userCount());
  }

  /**
   * Unknown users are created.
   *
   * @covers ::authenticate
   */
  public function testUserCreation(): void {
    $this->queueJwks([$this->main()]);

    $account = $this->authenticate($this->tokenFor('newbie', 'newbie@example.test'));
    $this->assertFalse($account->isNew());
    $loaded = User::load($account->id());
    $this->assertSame('newbie', $loaded->getAccountName());
    $this->assertSame('newbie@example.test', $loaded->getEmail());
    $this->assertTrue($loaded->isActive());
    $this->assertSame(['authenticated'], $loaded->getRoles());

    // No email: a placeholder address.
    $account = $this->authenticate($this->tokenFor('nomail', NULL));
    $this->assertSame('nomail@keycloak.com', User::load($account->id())->getEmail());

    // Verified flag without email: no lookup, placeholder address.
    $account = $this->authenticate($this->tokenFor('nomail2', '', ['email_verified' => TRUE]));
    $this->assertSame('nomail2@keycloak.com', User::load($account->id())->getEmail());

    // Same token again: the created user, no duplicate.
    $count = $this->userCount();
    $again = $this->authenticate($this->tokenFor('nomail2', ''));
    $this->assertSame((int) $account->id(), (int) $again->id());
    $this->assertSame($count, $this->userCount());
  }

  /**
   * The super admin (uid 1) and blocked users are refused.
   *
   * @covers ::authenticate
   */
  public function testRefusedAccounts(): void {
    $this->queueJwks([$this->main()]);
    // The super admin by name.
    $this->assertRefused($this->tokenFor('admin', NULL), 'Access denied');
    // The super admin by verified email.
    $admin_mail = $this->tokenFor('someone', 'admin@example.test');
    $this->assertRefused($admin_mail, 'Access denied');
    $this->assertSame(1, $this->userCount());

    $this->createAccount('blocked', 'blocked@example.test', 0);
    $by_name = $this->tokenFor('blocked', 'blocked@example.test');
    $this->assertRefused($by_name, 'Access denied');
    $by_mail = $this->tokenFor('other', 'blocked@example.test');
    $this->assertRefused($by_mail, 'Access denied');
    $this->assertSame(2, $this->userCount());
  }

  /**
   * Unknown "kid": one JWKS refresh, then failure, then throttling.
   *
   * @covers ::authenticate
   */
  public function testUnknownKidRefresh(): void {
    // Cached JWKS without the new key, refreshed JWKS with it.
    $this->queueJwks([$this->main()]);
    $rotated = $this->jwk('other', 'kid-rotated');
    $this->queueJwks([$this->main(), $rotated]);
    $this->assertAuthenticated('jdoe', $this->token());
    $this->assertCount(1, $this->history);

    $this->assertAuthenticated('jdoe', $this->token([], 'other', 'kid-rotated'));
    $this->assertCount(2, $this->history);
    $key = 'keycloak_jwks:' . self::CERTS;
    $refreshed = $this->cached($key . ':refreshed');
    $this->assertTrue($refreshed->data);
    $this->assertEqualsWithDelta(time() + 60, (int) $refreshed->expire, 30);
    $this->assertCount(2, $this->cached($key)->data['keys'], 'Refreshed JWKS cached.');

    // Within 60s another unknown "kid": refresh throttled, no request.
    $this->assertRefused($this->token([], 'other', 'kid-unknown'));
    $this->assertCount(2, $this->history);
  }

  /**
   * Unknown "kid" still unknown after the refresh.
   *
   * @covers ::authenticate
   */
  public function testUnknownKidAfterRefresh(): void {
    $this->queueJwks([$this->main()]);
    $this->queueJwks([$this->main()]);
    $this->assertRefused($this->token([], 'other', 'kid-unknown'));
    $this->assertCount(2, $this->history, 'Exactly one refresh.');
    $this->assertSame(1, $this->userCount());

    // Refresh failing at the HTTP level.
    $this->setSetting('KEYCLOAK_JWKS_URI', 'https://keys.example.test/jwks.json');
    $this->queueJwks([$this->main()]);
    $this->mockHandler->append(new Response(503));
    $this->assertRefused($this->token([], 'other', 'kid-unknown'));
    $this->assertCount(4, $this->history);

    // Token without "kid" while the JWKS has two keys: also retried once.
    $this->setSetting('KEYCLOAK_JWKS_URI', 'https://keys.example.test/other.json');
    $two_keys = [$this->main(), $this->jwk('other', 'kid-other')];
    $this->queueJwks($two_keys);
    $this->queueJwks($two_keys);
    $now = time();
    $jwt = JWT::encode([
      'iss' => self::ISSUER,
      'exp' => $now + 300,
      'preferred_username' => 'jdoe',
    ], static::key('main')['private'], 'RS256');
    $this->assertRefused($jwt);
    $this->assertCount(6, $this->history);
  }

  /**
   * The keycloak alter hook receives the user, claims and token.
   *
   * @covers ::authenticate
   */
  public function testAlterHook(): void {
    $this->queueJwks([$this->main()]);
    $custom = ['custom' => ['a' => 1]];
    $jwt = $this->tokenFor('hooked', 'hooked@example.test', $custom);
    $calls = [];
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->expects($this->once())->method('alter')
      ->willReturnCallback(function ($type, &$user, &$claims, &$token) use (&$calls) {
        $calls[] = [$type, $user, $claims, $token];
        // Implementations may replace the account.
        $user = User::load(1);
      });

    $provider = $this->provider();
    $set = function (string $property, $value) use ($provider) {
      $reflection = new \ReflectionProperty(SimpleOauthAuthenticationProvider::class, $property);
      $reflection->setAccessible(TRUE);
      $reflection->setValue($provider, $value);
    };
    $set('cache', \Drupal::cache('vactory_decoupled_oauth'));
    $set('moduleHandler', $module_handler);
    $method = new \ReflectionMethod(SimpleOauthAuthenticationProvider::class, 'authenticateKeycloak');
    $method->setAccessible(TRUE);
    $result = $method->invoke($provider, $jwt);

    $this->assertCount(1, $calls);
    [$type, $user, $claims, $token] = $calls[0];
    $this->assertSame('simple_oauth_authentication_keycloak', $type);
    $this->assertSame('hooked', $user->getAccountName());
    $this->assertFalse($user->isNew());
    $this->assertInstanceOf(DataSet::class, $claims);
    $this->assertSame($jwt, $claims->toString());
    $this->assertSame('hooked', $claims->get('preferred_username'));
    $this->assertSame(['a' => 1], $claims->get('custom'));
    $this->assertSame(self::ISSUER, $claims->get('iss'));
    $this->assertSame($jwt, $token);
    // The altered account is returned.
    $this->assertSame(1, (int) $result->id());
  }

  /**
   * The "Bearer " prefix is stripped and the token trimmed.
   *
   * @covers ::authenticate
   */
  public function testAuthorizationHeaderParsing(): void {
    $this->queueJwks([$this->main()]);
    $request = Request::create('/api/test');
    $request->headers->set('X-Auth-Provider', 'keycloak');
    $request->headers->set('Authorization', 'Bearer  ' . $this->token() . ' ');
    $this->assertSame('jdoe', $this->provider()->authenticate($request)->getAccountName());

    // Without the "Bearer " prefix the raw header value is used.
    $request->headers->set('Authorization', $this->token());
    $this->assertSame('jdoe', $this->provider()->authenticate($request)->getAccountName());
  }

}
