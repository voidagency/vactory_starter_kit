<?php

namespace Drupal\vactory_phone_auth_decoupled\Repositories;

use Drupal\user\UserAuthInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Drupal\simple_oauth\Entities\UserEntity;

/**
 * User repository class.
 */
class UserRepository implements UserRepositoryInterface {

  /**
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * UserRepository constructor.
   *
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The service to check the user authentication.
   */
  public function __construct(UserAuthInterface $user_auth) {
    $this->userAuth = $user_auth;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity) {
    // Login using phone number.
    if (is_numeric($username)) {
      $user_entity = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['field_telephone' => $username]);
      if ($user_entity != []) {
        $user_entity = reset($user_entity);
        $username = $user_entity->getEmail();
      }
    }

    if ($uid = $this->userAuth->authenticate($username, $password)) {
      $user = new UserEntity();
      $user->setIdentifier($uid);

      return $user;
    }

    return NULL;
  }

}
