<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Drupal\consumers\Entity\Consumer;

/**
 * Tests the login controller.
 *
 * @group vactory_decoupled
 */
class LoginControllerTest extends ExistingSiteBase {

  /**
   * Test user with admin role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $admin;

  /**
   * Admin password used for authentication.
   *
   * @var string
   */
  protected string $adminPassword;

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
   * Original OAuth key paths for restoration after tests.
   *
   * @var string
   */
  protected string $originalPublicKey;

  /**
   * Original OAuth key paths for restoration after tests.
   *
   * @var string
   */
  protected string $originalPrivateKey;

  /**
   * Directory for temporary test keys.
   *
   * @var string
   */
  protected string $keysDir;

  /**
   * Paths for the temporary private and public keys.
   *
   * @var string
   */
  protected string $privateKeyPath;

  /**
   * Paths for the temporary private and public keys.
   *
   * @var string
   */
  protected string $publicKeyPath;

  /**
   * Original flood configuration backup.
   *
   * @var array
   */
  protected array $originalFloodConfig = [];

  /**
   * Original flood settings backup.
   *
   * @var array
   */
  protected array $originalFloodSettings = [];

  /**
   * Backup of the original default consumer entity.
   *
   * @var \Drupal\consumers\Entity\Consumer|null
   */
  protected ?Consumer $originalDefaultConsumer = NULL;

  /**
   * Track modules installed during the test.
   *
   * @var string[]
   */
  protected array $modulesInstalledDuringTest = [];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Assurer que les modules sont installés.
    $this->ensureModuleInstalled('vactory_decoupled');
    $this->ensureModuleInstalled('vactory_decoupled_espace_prive');
    $this->ensureModuleInstalled('simple_oauth');
    $this->ensureModuleInstalled('consumers');

    $this->httpClient = \Drupal::httpClient();

    // Create and log in an admin user using DTT helper.
    $this->adminPassword = 'Admin@Void123';
    $this->admin = $this->createUser([], NULL, FALSE, ['pass' => $this->adminPassword]);

    // Génération des clés RSA.
    $this->keysDir = DRUPAL_ROOT . '/oauth-keys';
    if (!is_dir($this->keysDir)) {
      mkdir($this->keysDir, 0700, TRUE);
    }

    $this->privateKeyPath = $this->keysDir . '/private_test.key';
    $this->publicKeyPath = $this->keysDir . '/public_test.key';

    if (!file_exists($this->privateKeyPath) || !file_exists($this->publicKeyPath)) {
      $res = openssl_pkey_new([
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
      ]);
      openssl_pkey_export($res, $privateKey);
      $details = openssl_pkey_get_details($res);
      $publicKey = $details['key'];

      file_put_contents($this->privateKeyPath, $privateKey);
      file_put_contents($this->publicKeyPath, $publicKey);

      chmod($this->privateKeyPath, 0600);
      chmod($this->publicKeyPath, 0644);
    }

    // Sauvegarde des valeurs originales.
    $config = \Drupal::configFactory()->getEditable('simple_oauth.settings');
    $this->originalPublicKey = $config->get('public_key');
    $this->originalPrivateKey = $config->get('private_key');

    // Configuration temporaire pour les tests.
    $config->set('public_key', $this->publicKeyPath)
      ->set('private_key', $this->privateKeyPath)
      ->save();

    // Backup the original default consumer.
    $storage = \Drupal::entityTypeManager()->getStorage('consumer');
    $defaultConsumers = $storage->loadByProperties(['client_id' => 'default_consumer']);
    if ($defaultConsumers) {
      $this->originalDefaultConsumer = reset($defaultConsumers);
      $this->originalDefaultConsumer->delete();
    }

    // Récupérer l'URL de base du site actuel.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    // Callback.
    $redirect_uri = $base_url . '/api/auth/callback/drupal';

    $consumerData = $this->createTestConsumer($this->admin->id(), $redirect_uri);
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
      'username' => $this->admin->getAccountName(),
      'password' => $this->adminPassword,
    ]);

    // Assert HTTP status is 200.
    $this->assertEquals(200, $response['status'], 'HTTP status should be 200.');

    // Assert access token exists in response body.
    $this->assertArrayHasKey('access_token', $response['body'], 'Login should return an access token.');
    $this->assertNotEmpty($response['body']['access_token'], 'Access token should not be empty.');
  }

  /**
   * Test with non exesting user.
   */
  public function testLoginWithNonExistentUser(): void {
    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => 'not_existing_' . uniqid(),
      'password' => 'whatever',
    ]);

    $this->assertEquals(400, $response['status']);
    $this->assertArrayHasKey('error', $response['body']);
    $this->assertEquals('invalid_grant', $response['body']['error']);
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
    $this->admin->block();
    $this->admin->save();

    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => $this->admin->getAccountName(),
      'password' => $this->adminPassword,
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
      'username' => $this->admin->getAccountName(),
      'password' => $this->adminPassword,
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
    $config = \Drupal::configFactory()->getEditable('simple_oauth.settings');

    // Backup current keys.
    $originalPublicKey = $config->get('public_key');
    $originalPrivateKey = $config->get('private_key');

    // Cas 1 : chemins invalides.
    $config->set('public_key', '/invalid/path/public.key')
      ->set('private_key', '/invalid/path/private.key')
      ->save();

    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => $this->admin->getAccountName(),
      'password' => $this->adminPassword,
    ]);

    // Assert HTTP status is 500.
    $this->assertEquals(500, $response['status'], 'HTTP status should be 500.');

    // Assert error exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Should return an error.');
    $this->assertEquals('server_error', $response['body']['error'], 'Invalid keys should return server_error.');

    // Cas 2 : clés manquantes.
    $config->set('public_key', '')
      ->set('private_key', '')
      ->save();

    $response = $this->postJson('/oauth/login-token', [
      'grant_type' => 'password',
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
      'username' => $this->admin->getAccountName(),
      'password' => $this->adminPassword,
    ]);

    // Assert HTTP status is 500.
    $this->assertEquals(500, $response['status'], 'HTTP status should be 500.');

    // Assert error exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Should return an error.');
    $this->assertEquals('server_error', $response['body']['error'], 'Should return an error for server_error.');

    // Restore original keys.
    $config->set('public_key', $originalPublicKey)
      ->set('private_key', $originalPrivateKey)
      ->save();
  }

  /**
   * Test Drupal flood control per user (too many failed login attempts).
   */
  public function testFloodControlPerUser(): void {
    // Récupérer la config editable.
    $config = \Drupal::configFactory()->getEditable('user.flood');

    // Sauvegarder les valeurs originales.
    $this->originalFloodConfig = [
      'user_limit' => $config->get('user_limit'),
      'user_window' => $config->get('user_window'),
    ];

    // Config temporaire pour le test.
    $config->set('user_limit', 3)
      ->set('user_window', 60)
      ->save();

    $userLimit = 3;

    // Perform failed login attempts.
    for ($i = 0; $i < $userLimit + 1; $i++) {
      $response = $this->postJson('/oauth/login-token', [
        'grant_type' => 'password',
        'client_id' => $this->clientId,
        'client_secret' => $this->clientSecret,
        'username' => $this->admin->getAccountName(),
        'password' => 'wrong_password_' . $i,
      ]);
    }

    // Assert HTTP status is 500.
    $this->assertEquals(400, $response['status'], 'HTTP status should be 400.');

    // Assert error exists in response body.
    $this->assertArrayHasKey('error', $response['body'], 'Should return an error.');
    $this->assertEquals('flood_control_error', $response['body']['error'], 'Should return flood_control_error .');
  }

  /**
   * Test flood control basé uniquement sur l'adresse IP.
   */
  public function testFloodControlPerIpBlock(): void {
    $floodConfig = \Drupal::configFactory()->getEditable('user.flood');

    // Sauvegarder la config originale.
    $this->originalFloodConfig = [
      'ip_limit' => $floodConfig->get('ip_limit'),
      'ip_window' => $floodConfig->get('ip_window'),
      'user_limit' => $floodConfig->get('user_limit'),
      'user_window' => $floodConfig->get('user_window'),
    ];

    // Config temporaire pour tester rapidement.
    $floodConfig->set('ip_limit', 2)
      ->set('ip_window', 60)
      ->set('user_limit', 50)
      ->set('user_window', 600)
      ->save();

    // Effectuer plusieurs tentatives échouées avec le même user.
    for ($i = 0; $i < 3; $i++) {
      $response = $this->postJson('/oauth/login-token', [
        'grant_type' => 'password',
        'client_id' => $this->clientId,
        'client_secret' => $this->clientSecret,
        'username' => $this->admin->getAccountName(),
        'password' => 'wrong_password_' . $i,
      ]);
    }

    // Vérifier que c’est bien un blocage IP.
    $this->assertEquals(400, $response['status'], 'HTTP status should be 400 when IP is blocked.');
    $this->assertArrayHasKey('error', $response['body'], 'Response should contain an error.');
    $this->assertEquals('flood_control_error', $response['body']['error'], 'Error type should be flood_control_error.');
    $this->assertArrayHasKey('message', $response['body'], 'Response should contain a message.');
    $this->assertStringContainsString('adresse IP', $response['body']['message'], 'Message should mention IP flood specifically.');

    // Restaurer la config originale.
    $floodConfig->set('ip_limit', $this->originalFloodConfig['ip_limit'])
      ->set('ip_window', $this->originalFloodConfig['ip_window'])
      ->set('user_limit', $this->originalFloodConfig['user_limit'])
      ->set('user_window', $this->originalFloodConfig['user_window'])
      ->save();
  }

  /**
   * Test flood control messages depending on the config flag.
   */
  public function testFloodControlMessageDisplayFlag(): void {
    $floodConfig = \Drupal::configFactory()->getEditable('user.flood');
    $floodSettings = \Drupal::configFactory()
      ->getEditable('vactory_flood_control.settings');
    $vactoryFloodSettings = $floodSettings->getRawData();

    // Sauvegarder toutes les valeurs originales.
    $this->originalFloodConfig = [
      'ip_limit' => $floodConfig->get('ip_limit'),
      'ip_window' => $floodConfig->get('ip_window'),
      'user_limit' => $floodConfig->get('user_limit'),
      'user_window' => $floodConfig->get('user_window'),
    ];
    $this->originalFloodSettings = [
      'flood_message_display' => $vactoryFloodSettings['flood_message_display'] ?? NULL,
      'ip_whitelist' => $vactoryFloodSettings['ip_whitelist'] ?? NULL,
      'notification_emails' => $vactoryFloodSettings['notification_emails'] ?? NULL,
      'emails' => $vactoryFloodSettings['emails'] ?? NULL,
      'user_flood_notification' => $vactoryFloodSettings['user_flood_notification'] ?? [],
    ];

    // Config temporaire pour le test.
    $floodConfig->set('user_limit', 2)
      ->set('user_window', 60)
      ->set('ip_limit', 30)
      ->set('ip_window', 3600)
      ->save();

    // Test avec flood_message_display = TRUE.
    $floodSettings->set('flood_message_display', TRUE)->save();

    for ($i = 0; $i < 3; $i++) {
      $response = $this->postJson('/oauth/login-token', [
        'grant_type' => 'password',
        'client_id' => $this->clientId,
        'client_secret' => $this->clientSecret,
        'username' => $this->admin->getAccountName(),
        'password' => 'wrong_password_' . $i,
      ]);
    }

    $this->assertEquals(400, $response['status']);
    $this->assertArrayHasKey('message', $response['body']);
    $this->assertStringContainsString('There have been more than', $response['body']['message']);

    // Test avec flood_message_display = FALSE.
    $floodSettings->set('flood_message_display', FALSE)->save();

    // Réinitialiser flood.
    $floodConfig->set('user_limit', 2)->set('user_window', 60)->save();

    for ($i = 0; $i < 3; $i++) {
      $response = $this->postJson('/oauth/login-token', [
        'grant_type' => 'password',
        'client_id' => $this->clientId,
        'client_secret' => $this->clientSecret,
        'username' => $this->admin->getAccountName(),
        'password' => 'wrong_password_' . $i,
      ]);
    }

    $this->assertEquals(400, $response['status']);
    $this->assertArrayHasKey('message', $response['body']);
    $this->assertEquals('The account information provided was invalid.', $response['body']['message']);

    // Restaurer toutes les configs originales.
    $floodConfig->set('ip_limit', $this->originalFloodConfig['ip_limit'])
      ->set('ip_window', $this->originalFloodConfig['ip_window'])
      ->set('user_limit', $this->originalFloodConfig['user_limit'])
      ->set('user_window', $this->originalFloodConfig['user_window'])
      ->save();

    $floodSettings->set('flood_message_display', $this->originalFloodSettings['flood_message_display'])
      ->set('ip_whitelist', $this->originalFloodSettings['ip_whitelist'])
      ->set('notification_emails', $this->originalFloodSettings['notification_emails'])
      ->set('emails', $this->originalFloodSettings['emails'])
      ->set('user_flood_notification', $this->originalFloodSettings['user_flood_notification'])
      ->save();
  }

  /**
   * Crée un Consumer OAuth de test avec un client_id et client_secret.
   */
  protected function createTestConsumer(int $user_id, string $redirect_uri): array {
    $storage = \Drupal::entityTypeManager()->getStorage('consumer');

    // Génération du secret connu pour le test.
    $clientSecret = 'test_secret_' . uniqid();

    // Création du consumer.
    $consumer = $storage->create([
      'label' => 'Test Consumer',
      'client_id' => 'test_consumer_' . uniqid(),
      'description' => 'This is a test consumer created programmatically for automated tests.',
      'is_default' => TRUE,
      'redirect' => $redirect_uri,
      'roles' => [],
      'confidential' => TRUE,
      'owner_id' => $user_id,
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
   * Ensure a module is installed and track if we installed it during the test.
   */
  protected function ensureModuleInstalled(string $module_name): void {
    $moduleHandler = \Drupal::service('module_handler');
    $moduleInstaller = \Drupal::service('module_installer');

    if (!$moduleHandler->moduleExists($module_name)) {
      $moduleInstaller->install([$module_name]);
      $this->modulesInstalledDuringTest[] = $module_name;
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

    // Restore the original default consumer.
    if ($this->originalDefaultConsumer) {
      // Re-save it to restore.
      $storage = \Drupal::entityTypeManager()->getStorage('consumer');
      $restored = $storage->create($this->originalDefaultConsumer->toArray());
      $restored->save();
    }

    // Restaurer la configuration originale.
    $config = \Drupal::configFactory()->getEditable('simple_oauth.settings');
    $config->set('public_key', $this->originalPublicKey)
      ->set('private_key', $this->originalPrivateKey)
      ->save();

    // Supprimer les fichiers de test.
    if (file_exists($this->privateKeyPath)) {
      unlink($this->privateKeyPath);
    }
    if (file_exists($this->publicKeyPath)) {
      unlink($this->publicKeyPath);
    }

    // Restaurer la config flood originale.
    if (!empty($this->originalFloodConfig)) {
      $config = \Drupal::configFactory()->getEditable('user.flood');
      foreach ($this->originalFloodConfig as $key => $value) {
        $config->set($key, $value);
      }
      $config->save();
    }

    // Clear flood entries for the current user_test.
    if ($this->admin) {
      $flood = \Drupal::service('flood');
      $flood->clear('user.failed_login_user', (string) $this->admin->id());
      $flood->clear('user.failed_login_ip');
    }

    // Clear last flood entries created by this test.
    $connection = \Drupal::database();

    // Supprimer les derniers floods.
    $floods = $connection->select('flood', 'f')
      ->fields('f', ['fid'])
      ->condition('event', [
        'user.failed_login_ip',
        'user.failed_login_user',
      ], 'IN')
      ->orderBy('fid', 'DESC')
      ->range(0, 10)
      ->execute()
      ->fetchCol();

    if (!empty($floods)) {
      $connection->delete('flood')
        ->condition('fid', $floods, 'IN')
        ->execute();
    }

    // Désinstaller les modules installés pendant le test.
    if (!empty($this->modulesInstalledDuringTest)) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->uninstall($this->modulesInstalledDuringTest);
    }

    parent::tearDown();
  }

}
