<?php

namespace Drupal\vactory_security_review\Plugin\SecurityCheck;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\security_review\Attribute\SecurityCheck;
use Drupal\security_review\CheckResult;
use Drupal\vactory_security_review\VactorySecurityCheckBase;

/**
 * Checks whether authentication failures log is enbaled.
 */
#[SecurityCheck(
  id: 'auth_failure_log',
  title: new TranslatableMarkup('Authentication failure logs'),
  description: new TranslatableMarkup('Checks whether authentication failures log is enabled.'),
  namespace: new TranslatableMarkup('Security Review'),
  success_message: new TranslatableMarkup('Authentication failures log is enabled.'),
  failure_message: new TranslatableMarkup('Authentication failures log is not enabled, <a href="/admin/config/security-review">Enable it now</a>'),
  help: [
    new TranslatableMarkup('All authentication activities, whether successful or unsuccessful, must be recorded.'),
  ]
)]
class LoginFailureLog extends VactorySecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  protected function doRun(bool $cli): void {
    $is_failed_login_log_enabled = $this->configFactory->get('security_review.checks')->get('log_failed_auth');
    $this->createResult($is_failed_login_log_enabled ? CheckResult::SUCCESS : CheckResult::FAIL);
  }

}
