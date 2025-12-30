<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Drupal\consumers\Entity\Consumer;

/**
 * Tests the login controller.
 *
 * @group vactory_decoupled
 */
class LoginControllerTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected array $modulesToInstall = [
    'vactory_decoupled',
    'vactory_decoupled_espace_prive',
    'simple_oauth',
    'consumers',
    'flood_control',
  ];

  const SSL_KEYS_DIR = DRUPAL_ROOT . '/test-oauth-keys';
  const USER_PASSWORD = 'User@VOID123';

  /**
   * Test user (authenticated).
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user;

  /**
   * OAuth client credentials.
   *
   * @var string
   */
  protected string $clientId;

  /**
   * OAuth client secret.
   *
   * @var string
   */
  protected string $clientSecret;

  /**
   * Consumer Entity.
   *
   * @var \Drupal\consumers\Entity\Consumer|null
   */
  protected ?Consumer $consumerEntity = NULL;

  /**
   * HTTP client used for requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = \Drupal::httpClient();

    // Login as ADMIN.
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Create and log in a user.
    $this->user = $this->createUser([], NULL, FALSE, ['pass' => self::USER_PASSWORD]);

    // Génération des clés RSA.
    $ssl_keys_dir = self::SSL_KEYS_DIR;
    if (!is_dir($ssl_keys_dir)) {
      mkdir($ssl_keys_dir, 0700, TRUE);
    }

    $publicKeyPath = $ssl_keys_dir . '/public_test.key';
    $privateKeyPath = $ssl_keys_dir . '/private_test.key';

    // Generate and persist a new RSA key pair private/public.
    if (!file_exists($publicKeyPath) || !file_exists($privateKeyPath)) {
      $res = openssl_pkey_new([
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
      ]);
      openssl_pkey_export($res, $privateKey);
      $details = openssl_pkey_get_details($res);
      $publicKey = $details['key'];

      file_put_contents($privateKeyPath, $privateKey);
      file_put_contents($publicKeyPath, $publicKey);

      chmod($privateKeyPath, 0600);
      chmod($publicKeyPath, 0644);
    }

    $this->modifyConfigValue('simple_oauth.settings', 'public_key', $publicKeyPath);
    $this->modifyConfigValue('simple_oauth.settings', 'private_key', $privateKeyPath);

    // Disable reCAPTCHA protection for tests by clearing protected routes.
    $this->modifyConfigValue('vactory_decoupled.settings', 'routes', '');

    $consumerData = $this->createTestConsumer();
    $this->clientId = $consumerData['client_id'];
    $this->clientSecret = $consumerData['client_secret'];
    $this->consumerEntity = $consumerData['entity'];
  }

  /**
   * Test a valid login returns an access token.
   */
  public function testValidLogin(): void {
    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => self::USER_PASSWORD,
    ]);

    // Assert HTTP status is 200.
    $this->assertEquals(200, $response['status'], 'HTTP status should be 200.');

    // Assert access token exists in response body.
    $this->assertArrayHasKey('access_token', $response['body'], 'Login should return an access token.');
    $this->assertNotEmpty($response['body']['access_token'], 'Access token should not be empty.');
  }

  /**
   * Test an invalid login with wrong credentials.
   */
  public function testInvalidLogin(): void {
    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => 'wrong_user',
      'password' => 'wrong_pass',
    ]);

    // Assert HTTP status is 400.
    $this->assertEquals(400, $response['status'], 'HTTP status should be 400.');

    // Assert access token exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Invalid Credentials should return "invalid_grant.');
    $this->assertEquals('invalid_grant', $response['body']['error'], 'Invalid Credentials should return "invalid_grant.');
  }

  /**
   * Test login attempt with a blocked user.
   */
  public function testBlockedUserLogin(): void {
    // Block the user.
    $this->user->block();
    $this->user->save();

    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => self::USER_PASSWORD,
    ]);

    // Assert HTTP status is 403.
    $this->assertEquals(403, $response['status'], 'HTTP status should be 403.');

    // Assert account_blocked exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Account Blocked should return en error.');
    $this->assertEquals('account_blocked', $response['body']['error'], 'Account Blocked should return "account_blocked.');
  }

  /**
   * Test login attempt with an invalid consumer (wrong client_id).
   */
  public function testInvalidConsumer(): void {
    // On génère un client_id aléatoire qui ne correspond à aucun Consumer.
    $fakeClientId = 'nonexistent_consumer_' . uniqid();

    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $fakeClientId,
      'client_secret' => 'wrong_secret',
      'username' => $this->user->getAccountName(),
      'password' => self::USER_PASSWORD,
    ]);
    // Assert HTTP status is 401.
    $this->assertEquals(401, $response['status'], 'HTTP status should be 401.');

    // Assert invalid_client exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Client authentication failed should return an error.');
    $this->assertEquals('invalid_client', $response['body']['error'], 'Client authentication failed should return "invalid_client.');
  }

  /**
   * Test login attempt with invalid or missing public/private keys.
   */
  public function testInvalidOrMissingKeys(): void {
    $this->modifyConfigValue('simple_oauth.settings', 'public_key', '/invalid/path/public.key');
    $this->modifyConfigValue('simple_oauth.settings', 'private_key', '/invalid/path/private.key');

    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => self::USER_PASSWORD,
    ]);

    // Assert HTTP status is 500.
    $this->assertEquals(500, $response['status'], 'HTTP status should be 500.');

    // Assert error exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Should return an error.');
    $this->assertEquals('server_error', $response['body']['error'], 'Invalid keys should return server_error.');

    $this->modifyConfigValue('simple_oauth.settings', 'public_key', '');
    $this->modifyConfigValue('simple_oauth.settings', 'private_key', '');

    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => self::USER_PASSWORD,
    ]);

    // Assert HTTP status is 500.
    $this->assertEquals(500, $response['status'], 'HTTP status should be 500.');

    // Assert error exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Should return an error.');
    $this->assertEquals('server_error', $response['body']['error'], 'Should return an error for server_error.');
  }

  /**
   * Test Drupal flood control per user (too many failed login attempts).
   */
  public function testFloodControlPerUser(): void {
    $userLimit = 3;
    $this->modifyConfigValue('user.flood', 'user_limit', $userLimit);

    // Perform failed login attempts.
    for ($i = 0; $i < $userLimit; $i++) {
      $this->postJson('/oauth/login-token', [
        'grant_type' => 'password',
        'client_id' => $this->clientId,
        'client_secret' => $this->clientSecret,
        'username' => $this->user->getAccountName(),
        'password' => 'wrong_password',
      ]);
    }

    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => self::USER_PASSWORD,
    ]);

    // Assert HTTP status is 500.
    $this->assertEquals(400, $response['status'], 'HTTP status should be 400.');

    // Assert error exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Should return an error.');
    $this->assertEquals('flood_control_error', $response['body']['error'], 'Should return flood_control_error .');
  }

  /**
   * Crée un Consumer OAuth de test avec un client_id et client_secret.
   */
  protected function createTestConsumer(): array {
    $storage = \Drupal::entityTypeManager()->getStorage('consumer');

    // Génération du secret connu pour le test.
    $clientSecret = 'test_secret_' . uniqid();

    // Création du consumer.
    $consumer = $storage->create([
      'label' => 'Test Consumer',
      'client_id' => 'test_consumer_' . uniqid(),
      'description' => 'This is a test consumer created programmatically for automated tests.',
      'roles' => [],
      'confidential' => TRUE,
      'secret' => $clientSecret,
    ]);

    // Save the consumer entity.
    $consumer->save();

    $clientId = $consumer->client_id->value;

    return [
      'client_id' => $clientId,
      'client_secret' => $clientSecret,
      'entity' => $consumer,
    ];
  }

  /**
   * Helper to send a POST request with JSON response.
   */
  protected function postJson(string $path, array $data): array {
    $url = Url::fromUserInput($path, ['absolute' => TRUE])
      ->toString();
    try {
      $response = $this->httpClient->post($url, [
        'form_params' => $data,
        'headers' => ['Accept' => 'application/json'],
        'http_errors' => FALSE,
      ]);

      $status = $response->getStatusCode();
      $body = json_decode($response->getBody()->getContents(), TRUE) ?: [];

      // Return both status and body.
      return [
        'status' => $status,
        'body' => $body,
      ];
    }
    catch (\Exception $e) {
      return [
        'status' => 0,
        'body' => ['error' => $e->getMessage()],
      ];
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    // Delete the test consumer.
    if ($this->consumerEntity) {
      $this->consumerEntity->delete();
    }

    $this->removeDirectory(self::SSL_KEYS_DIR);

    parent::tearDown();
  }

}
