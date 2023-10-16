<?php

namespace Drupal\vactory_faceid_auth\Plugin\Oauth2Grant;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_oauth\Plugin\Oauth2GrantBase;
use Drupal\user\UserAuthInterface;
use Drupal\vactory_faceid_auth\OAuth2\Server\Grant\FaceIdGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Face ID custom grant type plugin.
 *
 * @Oauth2Grant(
 *   id = "faceid",
 *   label = @Translation("Face ID")
 * )
 */
class FaceId extends Oauth2GrantBase {

  /**
   * User repository service.
   *
   * @var \League\OAuth2\Server\Repositories\UserRepositoryInterface
   */
  protected $userRepository;

  /**
   * Refresh token repository service.
   *
   * @var \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface
   */
  protected $refreshTokenRepository;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * User auth service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UserRepositoryInterface $user_repository,
    RefreshTokenRepositoryInterface $refresh_token_repository,
    ConfigFactoryInterface $config_factory,
    UserAuthInterface $userAuth,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userRepository = $user_repository;
    $this->refreshTokenRepository = $refresh_token_repository;
    $this->configFactory = $config_factory;
    $this->userAuth = $userAuth;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_oauth.repositories.user'),
      $container->get('simple_oauth.repositories.refresh_token'),
      $container->get('config.factory'),
      $container->get('user.auth'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantType() {
    $grant = new FaceIdGrant(
      $this->userRepository,
      $this->refreshTokenRepository,
      $this->userAuth,
      $this->entityTypeManager
    );
    $settings = $this->configFactory->get('simple_oauth.settings');
    $grant->setRefreshTokenTTL(new \DateInterval(sprintf('PT%dS', $settings->get('refresh_token_expiration'))));
    return $grant;
  }

}
