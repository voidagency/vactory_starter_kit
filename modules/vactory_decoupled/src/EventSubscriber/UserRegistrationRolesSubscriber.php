<?php

namespace Drupal\vactory_decoupled\EventSubscriber;

use Drupal\jsonapi_user_resources\Events\RegistrationEvent;
use Drupal\jsonapi_user_resources\Events\UserResourcesEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Keeps role assignment server-side for JSON:API user registration.
 *
 * Roles on accounts created through the public registration resource must be
 * decided by the site, not by the request payload. This subscriber clears any
 * roles present on the new account before it is saved; a default role, if
 * wanted, should be added in a REGISTRATION_COMPLETE handler.
 *
 * @see \Drupal\jsonapi_user_resources\Resource\Registration
 */
class UserRegistrationRolesSubscriber implements EventSubscriberInterface {

  /**
   * Clears request-supplied roles from the account being registered.
   *
   * @param \Drupal\jsonapi_user_resources\Events\RegistrationEvent $event
   *   The registration validate event, dispatched before the account is saved.
   */
  public function onRegistrationValidate(RegistrationEvent $event) {
    $account = $event->getUser();
    // getRoles(TRUE) returns only the non-locked roles actually set on the
    // entity (never the implicit "authenticated" role).
    foreach ($account->getRoles(TRUE) as $role_id) {
      $account->removeRole($role_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run early (high priority) so roles are dropped before any other
    // subscriber acts on the account.
    return [
      UserResourcesEvents::REGISTRATION_VALIDATE => ['onRegistrationValidate', 1000],
    ];
  }

}
