<?php

namespace Drupal\vactory_taxonomy_results\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Term result count Access Control Handler
 *
 * @package Drupal\vactory_taxonomy_results
 */
class TermResultCountAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritDoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');

      case 'edit' || 'delete':
        return AccessResult::allowedIfHasPermission($account, 'access termresultscount overview');
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritDoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'access termresultscount overview');
  }
  
}
