<?php

namespace Drupal\vactory_oauth_apikey\OAuth2\Server\Grant;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_oauth\Entities\UserEntity;
use Drupal\user\UserAuthInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Password grant class.
 */
class ApikeyGrant extends PasswordGrant {

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
   * {@inheritDoc}
   */
  public function __construct(
    UserRepositoryInterface $userRepository,
    RefreshTokenRepositoryInterface $refreshTokenRepository,
    UserAuthInterface $userAuth,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($userRepository, $refreshTokenRepository);
    $this->userAuth = $userAuth;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client) {
    $apikey = $this->getRequestParameter('apikey', $request);
    if (!\is_string($apikey)) {
      throw OAuthServerException::invalidRequest('apikey');
    }

    $user = $this->getUserEntityByApikey($apikey);

    if ($user instanceof UserEntityInterface === FALSE) {
      $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

      throw OAuthServerException::invalidCredentials();
    }

    return $user;
  }

  /**
   * Get user entity by user face id.
   */
  protected function getUserEntityByApikey($apikey) {
    $api_key_entities = $this->entityTypeManager->getStorage('api_key')
      ->loadByProperties(['key' => $apikey]);
    if (count($api_key_entities) !== 1) {
      return NULL;
    }
    $api_key_entity = reset($api_key_entities);

    $accounts = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['uid' => $api_key_entity->user_uuid]);
    if (count($accounts) !== 1) {
      return NULL;
    }
    $account = reset($accounts);

    if ($account->isBlocked()) {
      return NULL;
    }

    $user_entity = new UserEntity();
    $user_entity->setIdentifier($account->id());
    return $user_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentifier() {
    return 'apikey';
  }

}
