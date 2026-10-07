<?php

namespace Drupal\vactory_webform_typesafe\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Checks both classification permissions and submission ownership context.
 */
final class AnalysisAccess {

  /**
   * Checks the route context and underlying submission access.
   */
  public function access(WebformInterface $webform, WebformSubmissionInterface $webform_submission, AccountInterface $account) {
    return AccessResult::allowedIf($webform_submission->getWebform()->id() === $webform->id())
      ->andIf(AccessResult::allowedIfHasPermission($account, 'view vactory webform typesafe'))
      ->andIf($webform_submission->access('view', $account, TRUE))
      ->addCacheableDependency($webform_submission);
  }

}
