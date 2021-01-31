<?php

namespace Drupal\vactory_appointment;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class AppointmentsAccessControlHandler
 *
 * @package Drupal\vactory_appointment
 */
class AppointmentsAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view appointments');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit appointments');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete appointments');
    }
    return AccessResult::allowed();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add appointments');
  }
}
