<?php

namespace Drupal\vactory_decoupled\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi_user_resources\Events\RegistrationEvent;
use Drupal\jsonapi_user_resources\Events\UserResourcesEvents;
use Drupal\user\RoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Keeps role assignment server-side for JSON:API user registration.
 *
 * Roles on accounts created through the public registration resource must be
 * decided by the site, not by the request payload. This subscriber clears any
 * roles present on the new account before it is saved, then adds the default
 * roles chosen in the back office ("Registration" tab of the decoupled
 * settings, config vactory_decoupled.settings:registration_default_roles).
 * Administrator, anonymous and authenticated roles are never added.
 *
 * The "espace privé" registration webform applies the same default roles
 * through defaultRoles().
 *
 * @see vactory_decoupled_espace_prive_decoupled_webform_data_presubmit_alter()
 * @see \Drupal\jsonapi_user_resources\Resource\Registration
 * @see \Drupal\vactory_decoupled\Form\DecoupledSettingsForm
 */
class UserRegistrationRolesSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the subscriber.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Replaces request-supplied roles by the configured default roles.
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
    foreach ($this->defaultRoles() as $role_id) {
      $account->addRole($role_id);
    }
  }

  /**
   * Returns the configured default roles that may be granted at registration.
   *
   * @return string[]
   *   Ids of existing, non-admin, non-locked roles.
   */
  public function defaultRoles(): array {
    $configured = $this->configFactory->get('vactory_decoupled.settings')->get('registration_default_roles') ?? [];
    if (!$configured) {
      return [];
    }
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple($configured);
    $allowed = array_filter($roles, static fn(RoleInterface $role) => self::isGrantable($role));
    return array_keys($allowed);
  }

  /**
   * Whether a role may be granted automatically at registration.
   */
  public static function isGrantable(RoleInterface $role): bool {
    return !$role->isAdmin() && !in_array($role->id(), [
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run early (high priority) so roles are set before any other subscriber
    // acts on the account.
    return [
      UserResourcesEvents::REGISTRATION_VALIDATE => [
        'onRegistrationValidate',
        1000,
      ],
    ];
  }

}
