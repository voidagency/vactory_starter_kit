<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;
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
   * Class constructor.
   */
  public function __construct(
    UserRepositoryInterface $user_repository,
    AccessTokenRepositoryInterface $access_token_repository,
    RefreshTokenRepositoryInterface $refresh_token_repository,
    ClientRepositoryInterface $client_repository,
    ConfigFactoryInterface $config_factory)
  {
    $this->userRepository = $user_repository;
    $this->refreshTokenRepository = $refresh_token_repository;
    $this->clientRepository = $client_repository;
    $this->accessTokenRepository = $access_token_repository;
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
      $container->get('config.factory')
    );
  }

  /**
   * Processes POST requests to /oauth/one-time-token.
   */
  public function token() {
    // Initialize the crypto key, optionally disabling the permissions check.
    $crypt_key = new CryptKey(
      $this->fileSystem()->realpath($this->privateKeyPath),
      NULL,
      Settings::get('simple_oauth.key_permissions_check', TRUE)
    );

    $grant = new PasswordGrant($this->userRepository, $this->refreshTokenRepository);
    $grant->setAccessTokenRepository($this->accessTokenRepository);
    $grant->setPrivateKey($crypt_key);
    $settings = $this->configFactory->get('simple_oauth.settings');
    $accessTokenTTL = new \DateInterval(sprintf('PT%dS', $settings->get('refresh_token_expiration')));
    // @todo: client ID
    $client = $this->clientRepository->getClientEntity("3087e3a0-0833-4ba3-8e47-c14d3fc7d19f");

    $abstractGrantReflection = new \ReflectionClass($grant);
    $issueAccessTokenMethod = $abstractGrantReflection->getMethod('issueAccessToken');
    $issueAccessTokenMethod->setAccessible(true);

    // @todo: make sure to get hash, timestamp & uid, and try to load the user.
    $accessToken = $issueAccessTokenMethod->invoke(
      $grant,
      $accessTokenTTL,
      $client,
      1,
      []
    );

    $issueRefreshTokenMethod = $abstractGrantReflection->getMethod('issueRefreshToken');
    $issueRefreshTokenMethod->setAccessible(true);
    $refreshToken = $issueRefreshTokenMethod->invoke($grant, $accessToken);

    $responseType = new BearerTokenResponse();
    $responseType->setPrivateKey($crypt_key);
    $responseType->setEncryptionKey(\base64_encode(\random_bytes(36))); // @todo: a big no for this

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
