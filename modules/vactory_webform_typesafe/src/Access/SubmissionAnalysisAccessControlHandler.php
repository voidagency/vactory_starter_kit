<?php

namespace Drupal\vactory_webform_typesafe\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Protects analysis content with the underlying submission access rules.
 */
final class SubmissionAnalysisAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $submission = $entity->get('submission')->entity;
    if (!$submission || $operation !== 'view') {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    return AccessResult::allowedIfHasPermission($account, 'view vactory webform typesafe')
      ->andIf($submission->access('view', $account, TRUE))->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::forbidden();
  }

}
