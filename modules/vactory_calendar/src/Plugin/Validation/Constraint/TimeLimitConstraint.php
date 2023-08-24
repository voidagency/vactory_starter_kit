<?php

namespace Drupal\vactory_calendar\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a TimeLimitConstraint constraint.
 *
 * @Constraint(
 *   id = "time_limit",
 *   label = @Translation("TimeLimitConstraint", context = "Validation"),
 * )
 */
class TimeLimitConstraint extends Constraint {

  /**
   * Constraint error message.
   *
   * @var string
   */
  public $intervalMessage = 'Un rendez-vous ne peut pas dépasser @placeholder minutes .';

  /**
   * Constraint error message.
   *
   * @var string
   */
  public $beginMessage = 'Les RDVs commencent à @placeholder .';

  /**
   * Constraint error message.
   *
   * @var string
   */
  public $endMessage = 'Les RDVs sont clôturés à @placeholder .';

  /**
   * {@inheritDoc}
   */
  public function validatedBy() {
    return TimeLimitConstraintValidator::class;
  }

}
