<?php

namespace Drupal\vactory_phone_auth_decoupled\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Checks if a user's phone is unique on the site.
 *
 * @Constraint(
 *   id = "UserPhoneUnique",
 *   label = @Translation("User phone  unique", context = "Validation")
 * )
 */
class UserPhoneUnique extends UniqueFieldConstraint {

  /**
   * @var string
   */
  public $message = 'The phone %value is already taken.';

}
