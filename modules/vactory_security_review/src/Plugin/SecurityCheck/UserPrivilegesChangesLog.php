<?php

namespace Drupal\vactory_security_review\Plugin\SecurityCheck;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\security_review\Attribute\SecurityCheck;
use Drupal\security_review\CheckResult;
use Drupal\vactory_security_review\VactorySecurityCheckBase;

/**
 * Checks whether user privileges changes log is enabled.
 */
#[SecurityCheck(
  id: 'user_privileges_log',
  title: new TranslatableMarkup('User privileges changes logs'),
  description: new TranslatableMarkup('Checks whether user privileges changes log is enabled.'),
  namespace: new TranslatableMarkup('Security Review'),
  success_message: new TranslatableMarkup("User's privilege level changes log is enabled."),
  failure_message: new TranslatableMarkup('User\'s privilege level changes log is not enabled, <a href="/admin/config/security-review">Enable it now</a>'),
  help: [
    new TranslatableMarkup("All activities or occasions where the user's privilege level changes should be recorded."),
  ]
)]
class UserPrivilegesChangesLog extends VactorySecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  protected function doRun(bool $cli): void {
    $log_user_privileges_change = $this->configFactory->get('security_review.checks')->get('log_user_privileges_change');
    $this->createResult($log_user_privileges_change ? CheckResult::SUCCESS : CheckResult::FAIL);
  }

}
