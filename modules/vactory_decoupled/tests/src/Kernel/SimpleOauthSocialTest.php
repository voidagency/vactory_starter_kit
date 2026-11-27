<?php

namespace Drupal\Tests\vactory_decoupled\Kernel;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\vactory_decoupled\SimpleOauthAuthenticationProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Google and Facebook paths of the Simple OAuth provider (Redmine #71366).
 *
 * An existing account is linked only by a verified email, never by its
 * name; the super admin, blocked accounts and admin roles are refused; the
 * audience is checked when it is configured. The providers are a mocked
 * HTTP client.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled\SimpleOauthAuthenticationProvider
 * @group vactory_decoupled
 */
class SimpleOauthSocialTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * Queued provider responses.
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
    $container->register('cache.vactory_decoupled_oauth', MemoryBackend::class)
      ->addArgument(new Reference('datetime.time'));
    $container->register('vactory_decoupled.user_manager', \stdClass::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
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
  }

  /**
   * Creates an account.
   */
  protected function createAccount(string $name, string $mail, int $status = 1, array $roles = []) {
    $user = User::create([
      'name' => $name,
      'mail' => $mail,
      'status' => $status,
      'roles' => $roles,
    ]);
    $user->save();
    return $user;
  }

  /**
   * Queues JSON responses of the provider.
   */
  protected function queue(array ...$bodies): void {
    foreach ($bodies as $body) {
      $headers = ['Content-Type' => 'application/json'];
      $this->mockHandler->append(new Response(200, $headers, json_encode($body)));
    }
  }

  /**
   * Authenticates a bearer token for a provider.
   */
  protected function authenticate(string $provider, string $token) {
    $request = Request::create('/api/test');
    $request->headers->set('X-Auth-Provider', $provider);
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $class = new \ReflectionClass(SimpleOauthAuthenticationProvider::class);
    $instance = $class->newInstanceWithoutConstructor();
    return $instance->authenticate($request);
  }

  /**
   * Asserts a refused token.
   */
  protected function assertRefused(string $provider, string $token, string $message): void {
    try {
      $this->authenticate($provider, $token);
      $this->fail('UnauthorizedHttpException expected.');
    }
    catch (UnauthorizedHttpException $e) {
      $this->assertSame($message, $e->getMessage());
      $this->assertSame(401, $e->getStatusCode());
      $realm = 'Bearer realm="' . $provider . '"';
      $this->assertSame(['WWW-Authenticate' => $realm], $e->getHeaders());
    }
  }

  /**
   * Google userinfo payload.
   */
  protected function google(string $sub, string $email, $verified = TRUE, array $extra = []): array {
    return ['sub' => $sub, 'email' => $email, 'email_verified' => $verified] + $extra;
  }

  /**
   * Facebook /me payload.
   */
  protected function facebook(string $id, string $email): array {
    return ['id' => $id, 'name' => 'A B', 'email' => $email];
  }

  /**
   * Facebook debug_token payload.
   */
  protected function debugToken(bool $valid, string $app_id): array {
    return ['data' => ['is_valid' => $valid, 'app_id' => $app_id]];
  }

  /**
   * Number of accounts.
   */
  protected function userCount(): int {
    return (int) \Drupal::entityQuery('user')
      ->accessCheck(FALSE)
      ->condition('uid', 0, '>')
      ->count()
      ->execute();
  }

  /**
   * A verified Google email links the existing account.
   */
  public function testGoogleLinksByVerifiedEmail(): void {
    $bob = $this->createAccount('bob', 'bob@example.test');
    $this->queue($this->google('g1', 'bob@example.test'));
    $this->assertSame($bob->id(), $this->authenticate('google', 'tok-1')->id());
    $this->assertCount(1, $this->history, 'No tokeninfo call without GOOGLE_CLIENT_ID.');

    // Same token: served from the cache.
    $this->assertSame($bob->id(), $this->authenticate('google', 'tok-1')->id());
    $this->assertCount(1, $this->history);
  }

  /**
   * An unverified Google email never links an existing account.
   */
  public function testGoogleUnverifiedEmailNotLinked(): void {
    $bob = $this->createAccount('bob', 'bob@example.test');
    foreach ([FALSE, 'false', NULL] as $i => $verified) {
      $this->queue($this->google('g2', 'bob@example.test', $verified));
      $user = $this->authenticate('google', 'tok-unverified-' . $i);
      $this->assertNotEquals($bob->id(), $user->id());
      $this->assertSame('g2@google.com', $user->getAccountName());
      $this->assertSame('g2@google.com', $user->getEmail());
    }
    // One account for this identity, reused.
    $this->assertSame(3, $this->userCount());
  }

  /**
   * An account is never linked by its name.
   */
  public function testNoLinkByName(): void {
    // An account whose name is the email of someone else.
    $eve = $this->createAccount('carol@example.test', 'eve@example.test');
    $this->queue($this->google('g3', 'carol@example.test'));
    $user = $this->authenticate('google', 'tok-3');
    $this->assertNotEquals($eve->id(), $user->id());
    // The name is taken: the new account uses the provider identity.
    $this->assertSame('g3@google.com', $user->getAccountName());
    $this->assertSame('carol@example.test', $user->getEmail());
  }

  /**
   * New accounts are created with the verified email as name.
   */
  public function testAccountCreation(): void {
    $this->queue($this->google('g4', 'dan@example.test', TRUE, ['given_name' => 'Dan']));
    $user = $this->authenticate('google', 'tok-4');
    $this->assertSame('dan@example.test', $user->getAccountName());
    $this->assertTrue($user->isActive());
    $this->assertSame([], $user->getRoles(TRUE));
  }

  /**
   * The super admin, blocked accounts and admin roles are refused.
   */
  public function testRefusedAccounts(): void {
    Role::create([
      'id' => 'administrator',
      'label' => 'Administrator',
      'is_admin' => TRUE,
    ])->save();
    $this->createAccount('blocked', 'blocked@example.test', 0);
    $this->createAccount('boss', 'boss@example.test', 1, ['administrator']);
    $before = $this->userCount();

    foreach (['admin', 'blocked', 'boss'] as $i => $name) {
      $this->queue($this->google('g-' . $name, $name . '@example.test'));
      $this->assertRefused('google', 'tok-google-' . $i, 'Access denied');
      $this->queue($this->facebook('f-' . $name, $name . '@example.test'));
      $this->assertRefused('facebook', 'tok-facebook-' . $i, 'Access denied');
    }
    $this->assertSame($before, $this->userCount());
  }

  /**
   * GOOGLE_CLIENT_ID: the token must be issued for this client.
   */
  public function testGoogleAudience(): void {
    $this->setSetting('GOOGLE_CLIENT_ID', ['client-a', 'client-b']);
    $bob = $this->createAccount('bob', 'bob@example.test');

    $this->queue(['aud' => 'other-app', 'azp' => 'other-app']);
    $this->assertRefused('google', 'tok-other', 'Access token could not be verified');
    $this->assertCount(1, $this->history, 'No userinfo call: other client.');
    $uri = (string) $this->history[0]['request']->getUri();
    $this->assertStringStartsWith('https://oauth2.googleapis.com/tokeninfo', $uri);

    $this->queue(['aud' => 'client-b'], $this->google('g1', 'bob@example.test'));
    $this->assertSame($bob->id(), $this->authenticate('google', 'tok-ok')->id());
  }

  /**
   * FACEBOOK_APP_ID and FACEBOOK_APP_SECRET: the token must be for this app.
   */
  public function testFacebookApplication(): void {
    $this->setSetting('FACEBOOK_APP_ID', '1234');
    $this->setSetting('FACEBOOK_APP_SECRET', 'app-secret');
    $bob = $this->createAccount('bob', 'bob@example.test');

    $this->queue($this->debugToken(TRUE, '9999'));
    $this->assertRefused('facebook', 'tok-other', 'Access token could not be verified');
    $this->queue($this->debugToken(FALSE, '1234'));
    $this->assertRefused('facebook', 'tok-invalid', 'Access token could not be verified');

    $this->queue($this->debugToken(TRUE, '1234'), $this->facebook('f1', 'bob@example.test'));
    $this->assertSame($bob->id(), $this->authenticate('facebook', 'tok-ok')->id());
    $this->assertStringContainsString('debug_token', (string) $this->history[2]['request']->getUri());
  }

  /**
   * A token the provider rejects gives a 401, not a server error.
   */
  public function testProviderError(): void {
    $this->mockHandler->append(new Response(401, [], '{"error":"invalid_token"}'));
    $this->assertRefused('google', 'tok-bad', 'Access token could not be verified');
    $this->queue(['id' => '']);
    $this->assertRefused('facebook', 'tok-empty', 'Access token could not be verified');
    $this->assertSame(1, $this->userCount());
  }

}
