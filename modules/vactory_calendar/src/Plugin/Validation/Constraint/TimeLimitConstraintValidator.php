<?php

namespace Drupal\vactory_calendar\Plugin\Validation\Constraint;

use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the upper and lower bound time constraint.
 */
class TimeLimitConstraintValidator extends ConstraintValidator {

  /**
   * @inheritDoc
   */
  public function validate($value, Constraint $constraint) {

    $validations = $this->formatConstraints();
    $entity = $value;
    if ($entity->hasField('start_time') && $entity->hasField('end_time') && !$this->isEmptyConstraints()) {
      $dateTimeBegin = new DrupalDateTime($entity->start_time->value);

      $dateTimeEnd = new DrupalDateTime($entity->end_time->value);

      $interval = $dateTimeBegin->diff($dateTimeEnd);

      $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

      // Check if the difference is greater than 15 minutes.
      if (!empty($validations['interval']) && $totalMinutes > $validations['interval']) {
        $this->context->buildViolation(str_replace('@placeholder', $validations['interval'], $constraint->intervalMessage))
          ->addViolation();
      }

      if (!empty($validations['begin']) && $dateTimeBegin->format('H:i:s') < $validations['begin']->format('H:i:s')) {
        $this->context->buildViolation(str_replace('@placeholder', $validations['begin']->format('H:i'), $constraint->beginMessage))
          ->addViolation();
      }

      if (!empty($validations['end']) && $dateTimeEnd->format('H:i:s') > $validations['end']->format('H:i:s')) {
        $this->context->buildViolation(str_replace('@placeholder', $validations['end']->format('H:i'), $constraint->endMessage))
          ->addViolation();
      }
    }
  }

  public function formatConstraints() {
    $config = \Drupal::config('vactory_calendar.settings');
    return [
      'interval' => $config->get('interval') ? (int) $config->get('interval') : '',
      'begin' => $config->get('begin') ? new DrupalDateTime($config->get('begin')) : '',
      'end' => $config->get('end') ? new DrupalDateTime($config->get('end')) : '',
    ];
  }

  /**
   * @return bool
   */
  private function isEmptyConstraints() {
    $not_set = TRUE;
    foreach ($this->formatConstraints() as $element) {
      $not_set &= empty($element);
    }
    return (bool) $not_set;
  }


}
