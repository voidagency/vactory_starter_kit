<?php

namespace Drupal\vactory_decoupled\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraint;

/**
 * Checks if the plain text password is provided for editing a protected field.
 *
 * @Constraint(
 *   id = "SspaProtectedUserField",
 *   label = @Translation("Password required for protected field change", context = "Validation")
 * )
 */
class SspaProtectedUserFieldConstraint extends ProtectedUserFieldConstraint {

}
