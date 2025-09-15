<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\user\Entity\User;
use Drupal\Core\Flood\FloodInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

/**
 * @group vactory_decoupled
 */
class LoginControllerTest extends ExistingSiteBase
{
    protected User $user;
    protected string $clientId = 'default_consumer';
    protected string $clientSecret = 'vactory8';

    protected User $admin;
    protected string $adminPassword;
    protected Node $node;
    protected ClientInterface $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = \Drupal::httpClient();

        // Create and log in an admin user using DTT helper.
        $this->adminPassword = 'Admin@Void123'; 
        $this->admin = $this->createUser([], NULL, TRUE, ['pass' => $this->adminPassword]);        
        $this->drupalLogin($this->admin);
    }

    protected function tearDown(): void
    {
        if ($this->admin) {

            $flood = \Drupal::service('flood');
            // Clear flood entries for the user and IP
            $identifier = (string) $this->admin->id();
            $flood->clear('user.failed_login_user', $identifier);

            $test_ip = '192.168.117.1'; // replace with the IP used in your test
            $flood->clear('user.failed_login_ip', $test_ip);

            $flood->clear('user.failed_login_ip');

            $this->admin->delete();
        }
        parent::tearDown();
    }

    public function testValidLogin(): void
    {

        $response = $this->postJson('/oauth/login-token', [
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $this->admin->getAccountName(),
            'password' => $this->adminPassword,
        ]);

        $this->assertArrayHasKey('access_token', $response);
        $this->assertNotEmpty($response['access_token']);
    }

    public function testInvalidLogin(): void
    {

        $response = $this->postJson('/oauth/login-token', [
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => 'wrong_user',
            'password' => 'wrong_pass',
        ]);

        $this->assertEquals('invalid_grant', $response['error']);
    }

    public function testBlockedUserLogin(): void
    {

        // Block the user
        $this->admin->block();
        $this->admin->save();

        // Attempt login via OAuth
        $response = $this->postJson('/oauth/login-token', [
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $this->admin->getAccountName(),
            'password' => 'password',
        ]);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('account_blocked', $response['error']);
    }

    public function testFloodControl(): void
    {
        $flood = \Drupal::service('flood');

        // Initialize flood limits
        $userLimit = 5; // max failed login attempts per user
        $userWindow = 6 * 3600; // 6 hours
        $userIpLimit = 10; // max failed login attempts per IP
        $userIpWindow = 3600; // 1 hour

        $identifier = (string) $this->admin->id();

        // Clear previous flood entries for user and IP
        $flood->clear('user.failed_login_user', $identifier);
        $flood->clear('user.failed_login_ip');

        $responses = [];
        $max_attempts = $userLimit;

        for ($i = 0; $i < $max_attempts + 2; $i++) {
            $response = $this->postJson('/oauth/login-token', [
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $this->admin->getAccountName(),
                'password' => 'wrong_password_' . $i,
            ]);

            $responses[] = $response['error'] ?? 'SUCCESS';
        }

        // Check flood status
        $userBlocked = !$flood->isAllowed('user.failed_login_user', $userLimit, $userWindow, $identifier);
        $ipBlocked = !$flood->isAllowed('user.failed_login_ip', $userIpLimit, $userIpWindow);
        // Assertions
        $this->assertTrue($userBlocked || $ipBlocked, 'Flood control should block login after multiple failed attempts.');
        $this->assertTrue($userBlocked, 'Flood control should block the user specifically after multiple failed attempts.');
    }

    protected function postJson(string $path, array $data): array
    {
        $url = \Drupal\Core\Url::fromUserInput($path, ['absolute' => TRUE])->toString();
        try {
            $response = $this->httpClient->post($url, [
                'form_params' => $data,
                'headers' => ['Accept' => 'application/json'],
                'http_errors' => false,
            ]);
            return json_decode($response->getBody()->getContents(), TRUE) ?: [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
