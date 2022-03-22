<?php

namespace Drupal\vactory_jsonapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is valid against reCaptcha services.
 *
 * @Constraint(
 *   id = "jsonapi_recaptcha",
 *   label = @Translation("reCaptcha", context = "Validation"),
 *   type = "string"
 * )
 */
class Recaptcha extends Constraint {

  // The message that will be shown if the value is missing.
  public $required = 'Le champs Captcha est requis';

  // The message that will be shown if the value is not valide.
  public $notValid = "Le champs Captcha n'est pas valide.";
}
