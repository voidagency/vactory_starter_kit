<?php

namespace Drupal\vactory_notifications;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class NotificationsAccessControlHandler
 *
 * @package Drupal\vactory_notifications
 */
class NotificationsAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view notifications');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit notifications');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete notifications');
    }
    return AccessResult::allowed();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add notifications');
  }
}
