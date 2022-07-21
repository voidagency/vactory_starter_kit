<?php

namespace Drupal\vactory_decoupled\Plugin\Validation\Constraint;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraintValidator;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Decorates the ProtectedUserFieldConstraint constraint.
 */
class SspaProtectedUserFieldConstraintValidator extends ProtectedUserFieldConstraintValidator {

  /**
   * Whether or not restricted password managment is enabled.
   *
   * @var bool
   */
  protected $restrictedPasswordManagement = TRUE; // @todo: setting for this

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   */
  public function __construct(UserStorageInterface $user_storage, AccountProxyInterface $current_user) {
    parent::__construct($user_storage, $current_user);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!isset($items)) {
      return;
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $field = $items->getFieldDefinition();

    /** @var \Drupal\user\UserInterface $account */
    $account = $items->getEntity();
    if (!isset($account) || !empty($account->_skipProtectedUserFieldConstraint)) {
      // Looks like we are validating a field not being part of a user, or the
      // constraint should be skipped, so do nothing.
      return;
    }

    // Only validate for existing entities and if this is the current user.
    if (!$account->isNew() && $account->id() == $this->currentUser->id()) {
      if ($field->getName() === 'mail') {
        return;
      }
    }

    parent::validate($items, $constraint);
  }

}
