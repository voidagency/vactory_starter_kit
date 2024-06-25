<?php

namespace Drupal\vactory_decoupled_espace_prive\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\password_policy\PasswordPolicyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates the Vactory password policy constraint.
 */
class VactoryPasswordPolicyConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {


  /**
   * Password policy validator.
   *
   * @var \Drupal\password_policy\PasswordPolicyValidator
   */
  private PasswordPolicyValidator $passwordPolicyValidator;

  /**
   * Constructs the object.
   */
  public function __construct(PasswordPolicyValidator $passwordPolicyValidator) {
    $this->passwordPolicyValidator = $passwordPolicyValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('password_policy.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!isset($value)) {
      return;
    }

    $field = $value->getFieldDefinition();
    if ($field->getName() != 'pass') {
      return;
    }

    $pass = $value->value;
    $account = $value->getEntity();

    $validation = $this->passwordPolicyValidator->validatePassword($pass, $account);
    if (!$validation->hasErrors()) {
      return;
    }

    $errors = $validation->getErrors()->render();
    $this->context->addViolation($constraint->message, ['%errors' => $errors]);

  }

}
