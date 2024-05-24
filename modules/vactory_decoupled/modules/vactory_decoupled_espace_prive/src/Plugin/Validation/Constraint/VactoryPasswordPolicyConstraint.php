<?php

namespace Drupal\vactory_decoupled_espace_prive\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks password policy.
 *
 * @Constraint(
 *   id = "VactoryPasswordPolicyConstraint",
 *   label = @Translation("Vactory password policy constraint", context = "Validation"),
 * )
 */
class VactoryPasswordPolicyConstraint extends Constraint {

  /**
   * Violation message.
   *
   * @var string
   */
  public $message = 'Password does not match password policy: %errors';

}
