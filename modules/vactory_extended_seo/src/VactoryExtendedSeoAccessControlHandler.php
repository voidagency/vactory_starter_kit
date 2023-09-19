<?php

namespace Drupal\vactory_extended_seo;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Vactory extended seo entity.
 *
 * @see \Drupal\vactory_extended_seo\Entity\VactoryExtendedSeo.
 */
class VactoryExtendedSeoAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\vactory_extended_seo\Entity\VactoryExtendedSeoInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished vactory extended seo entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published vactory extended seo entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit vactory extended seo entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete vactory extended seo entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add vactory extended seo entities');
  }


}
