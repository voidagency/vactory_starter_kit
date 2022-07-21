<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Grant\PasswordGrant;
use Drupal\Core\Config\ConfigFactoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use League\OAuth2\Server\CryptKey;
use Drupal\Core\Site\Settings;
use Drupal\Core\File\FileSystemInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Server\Exception\OAuthServerException;
use Drupal\simple_oauth\Entities\ClientEntity;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\UserStorageInterface;
use Defuse\Crypto\Core;

class OneTimeToken extends ControllerBase {

  /**
   * @var \League\OAuth2\Server\Repositories\UserRepositoryInterface
   */
  protected $userRepository;

  /**
   * @var \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface
   */
  protected $refreshTokenRepository;

  /**
   * @var \League\OAuth2\Server\Repositories\ClientRepositoryInterface
   */
  protected $clientRepository;

  /**
   * @var \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface
   */
  protected $accessTokenRepository;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var string
   */
  protected $privateKeyPath;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * @var \League\OAuth2\Server\Repositories\ScopeRepositoryInterface
   */
  protected $scopeRepository;

  /**
   * Class constructor.
   */
  public function __construct(
    UserRepositoryInterface $user_repository,
    AccessTokenRepositoryInterface $access_token_repository,
    RefreshTokenRepositoryInterface $refresh_token_repository,
    ClientRepositoryInterface $client_repository,
    ScopeRepositoryInterface $scope_repository,
    UserStorageInterface $user_storage,
    ConfigFactoryInterface $config_factory)
  {
    $this->userRepository = $user_repository;
    $this->refreshTokenRepository = $refresh_token_repository;
    $this->clientRepository = $client_repository;
    $this->accessTokenRepository = $access_token_repository;
    $this->scopeRepository = $scope_repository;
    $this->userStorage = $user_storage;
    $this->configFactory = $config_factory;
    $settings = $config_factory->get('simple_oauth.settings');
    $this->setKeyPaths($settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('simple_oauth.repositories.user'),
      $container->get('simple_oauth.repositories.access_token'),
      $container->get('simple_oauth.repositories.refresh_token'),
      $container->get('simple_oauth.repositories.client'),
      $container->get('simple_oauth.repositories.scope'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('config.factory')
    );
  }

  /**
   * Processes POST requests to /oauth/one-time-token.
   */
  public function token(ServerRequestInterface $request) {
    // Extract the grant type from the request body.
    $body = $request->getParsedBody();
    $client_id = $body['client_id'];
    $client_secret = $body['client_secret'];
    $uid = $body['uid'];
    $timestamp = $body['timestamp'];
    $hash = $body['hash'];
    $grant_type = 'implicit';
    $current = \Drupal::time()->getRequestTime();

    // Check for client arguements.
    if (empty($client_id) || empty($client_secret)) {
      return OAuthServerException::invalidClient($request)
        ->generateHttpResponse(new Response());
    }

    // Check for user arguments.
    if (empty($uid) || empty($timestamp) || empty($hash)) {
      return new JsonResponse([
        'error' => 'invalid_user',
        'error_description' => 'User authentication failed',
        'message' => 'User authentication failed'
      ], 401);
    }

    // Check if client exist.
    $drupal_client = $this->clientRepository->getClientDrupalEntity($client_id);
    if (empty($drupal_client)) {
      return OAuthServerException::invalidClient($request)
        ->generateHttpResponse(new Response());
    }

    // Validate client id & secret
    if (!$this->clientRepository->validateClient($client_id, $client_secret, $grant_type)) {
      return OAuthServerException::invalidClient($request)
        ->generateHttpResponse(new Response());
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    if ($user === NULL || !$user->isActive()) {
      return new JsonResponse([
        'error' => 'invalid_user',
        'error_description' => 'User authentication failed',
        'message' => 'User authentication failed'
      ], 401);
    }

    // Time out, in seconds, until login URL expires.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
      return new JsonResponse([
        'error' => 'one_time_login_expired',
        'error_description' => 'You have tried to use a one-time login link that has expired. Please request a new one using the form below.',
        'message' => 'You have tried to use a one-time login link that has expired. Please request a new one using the form below.'
      ], 401);
    }

    if (
      !($user->isAuthenticated() &&
      ($timestamp >= $user->getLastLoginTime()) &&
      ($timestamp <= $current) &&
      hash_equals($hash, user_pass_rehash($user, $timestamp))
      )
      ) {
      return new JsonResponse([
        'error' => 'one_time_login_failed',
        'error_description' => 'One-time-login user authentication failed',
        'message' => 'One-time-login user authentication failed'
      ], 401);
    }

    $client = new ClientEntity($drupal_client);

    // Initialize the crypto key, optionally disabling the permissions check.
    $crypt_key = new CryptKey(
      $this->fileSystem()->realpath($this->privateKeyPath),
      NULL,
      Settings::get('simple_oauth.key_permissions_check', TRUE)
    );
    $salt = Settings::getHashSalt();
    $encryptionKey = Core::ourSubstr($salt, 0, 32);

    $grant = new PasswordGrant($this->userRepository, $this->refreshTokenRepository);
    $grant->setAccessTokenRepository($this->accessTokenRepository);
    $grant->setPrivateKey($crypt_key);
    $settings = $this->configFactory->get('simple_oauth.settings');
    $accessTokenTTL = new \DateInterval(sprintf('PT%dS', $settings->get('access_token_expiration')));

    $abstractGrantReflection = new \ReflectionClass($grant);
    $issueAccessTokenMethod = $abstractGrantReflection->getMethod('issueAccessToken');
    $issueAccessTokenMethod->setAccessible(true);

    $scope = $this->scopeRepository->getScopeEntityByIdentifier("authenticated");
    $accessToken = $issueAccessTokenMethod->invoke(
      $grant,
      $accessTokenTTL,
      $client,
      $uid,
      [$scope]
    );

    $issueRefreshTokenMethod = $abstractGrantReflection->getMethod('issueRefreshToken');
    $issueRefreshTokenMethod->setAccessible(true);
    $refreshToken = $issueRefreshTokenMethod->invoke($grant, $accessToken);

    $responseType = new BearerTokenResponse();
    $responseType->setPrivateKey($crypt_key);
    $responseType->setEncryptionKey($encryptionKey);

    $responseType->setAccessToken($accessToken);
    $responseType->setRefreshToken($refreshToken);
    $response = $responseType->generateHttpResponse(new Response());
    return $response;
  }

  /**
   * Set the public and private key paths.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   The Simple OAuth settings configuration object.
   */
  protected function setKeyPaths(ImmutableConfig $settings)
  {
    // $this->publicKeyPath = $settings->get('public_key');
    $this->privateKeyPath = $settings->get('private_key');
  }

  /**
   * Lazy loads the file system.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   The file system service.
   */
  protected function fileSystem(): FileSystemInterface
  {
    if (!isset($this->fileSystem)) {
      $this->fileSystem = \Drupal::service('file_system');
    }
    return $this->fileSystem;
  }

}
