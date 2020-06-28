<?php

namespace Drupal\vactory_locator;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Locator Entity entity.
 *
 * @see \Drupal\vactory_locator\Entity\LocatorEntity.
 */
class LocatorEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\vactory_locator\Entity\LocatorEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished locator entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published locator entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit locator entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete locator entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add locator entity entities');
  }

}
