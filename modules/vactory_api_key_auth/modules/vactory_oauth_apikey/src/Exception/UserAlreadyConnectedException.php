<?php

namespace Drupal\vactory_oauth_apikey\Exception;

use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * Custom exception class for handling cases where a user is already connected.
 */
class UserAlreadyConnectedException extends OAuthServerException {

  /**
   * Creates a new UserAlreadyConnectedException.
   */
  public static function create() {
    return new static('User is already connected. Please log out from the other session first.', 10, 'access_denied', 400);
  }

}
