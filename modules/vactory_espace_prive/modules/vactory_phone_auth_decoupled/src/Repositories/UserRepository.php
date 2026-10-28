<?php

namespace Drupal\vactory_phone_auth_decoupled\Repositories;

use Drupal\Core\Session\AccountInterface;
use Drupal\simple_oauth_password_grant\Repository\UserRepository as PasswordGrantUserRepository;

/**
 * User repository class.
 *
 * Allows the password grant to authenticate users by phone number.
 */
class UserRepository extends PasswordGrantUserRepository {

  /**
   * {@inheritdoc}
   */
  protected function getAccount(string $usernameOrEmail): ?AccountInterface {
    // Login using phone number.
    if (is_numeric($usernameOrEmail)) {
      $account = $this->getAccountByProperty('field_telephone', $usernameOrEmail);
      if ($account) {
        return $account;
      }
    }

    return parent::getAccount($usernameOrEmail);
  }

}
