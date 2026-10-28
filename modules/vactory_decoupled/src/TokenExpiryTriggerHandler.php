<?php

namespace Drupal\vactory_decoupled;

use Drupal\consumers\Entity\ConsumerInterface;
use Drupal\simple_oauth\ExpiredCollector;
use Drupal\simple_oauth\TokenExpiryTriggerHandlerInterface;
use Drupal\user\UserInterface;

/**
 * Only revoke user tokens when the account security data changes.
 *
 * Simple OAuth revokes all user tokens on every user save, which logs the
 * user out from the frontend after a profile update.
 */
class TokenExpiryTriggerHandler implements TokenExpiryTriggerHandlerInterface {

  /**
   * The decorated token expiry trigger handler.
   *
   * @var \Drupal\simple_oauth\TokenExpiryTriggerHandlerInterface
   */
  protected $inner;

  /**
   * The expired tokens collector.
   *
   * @var \Drupal\simple_oauth\ExpiredCollector
   */
  protected $collector;

  /**
   * Class constructor.
   */
  public function __construct(TokenExpiryTriggerHandlerInterface $inner, ExpiredCollector $collector) {
    $this->inner = $inner;
    $this->collector = $collector;
  }

  /**
   * {@inheritdoc}
   */
  public function handleCron(): void {
    $this->inner->handleCron();
  }

  /**
   * {@inheritdoc}
   */
  public function handleUserUpdate(UserInterface $user): void {
    $original = method_exists($user, 'getOriginal') ? $user->getOriginal() : ($user->original ?? NULL);
    if (!$original instanceof UserInterface) {
      $this->inner->handleUserUpdate($user);
      return;
    }

    // Following the Drupal core pattern, revoke access to the system when
    // password or mail has been changed, the account got blocked or the roles
    // have changed.
    $password_changed = $user->getPassword() !== $original->getPassword();
    $mail_changed = $user->getEmail() !== $original->getEmail();
    $account_blocked = $user->isBlocked() && $original->isActive();
    $roles_new = $user->getRoles();
    $roles_old = $original->getRoles();
    $roles_changed = array_diff($roles_new, $roles_old) || array_diff($roles_old, $roles_new);

    if ($password_changed || $mail_changed || $account_blocked || $roles_changed) {
      $this->collector->deleteMultipleTokens($this->collector->collectForAccount($user));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleConsumerUpdate(ConsumerInterface $consumer): void {
    $this->inner->handleConsumerUpdate($consumer);
  }

  /**
   * {@inheritdoc}
   */
  public function handleConsumerDelete(ConsumerInterface $consumer): void {
    $this->inner->handleConsumerDelete($consumer);
  }

}
