<?php

namespace Drupal\vactory_security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks whether authentication failures log is enbaled.
 */
class LoginFailureLog extends Check {

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Authentication failure logs';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'auth_failure_log';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = [];
    $is_failed_login_log_enabled = \Drupal::config('security_review.checks')->get('log_failed_auth');
    if (!$is_failed_login_log_enabled) {
      $result = CheckResult::FAIL;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t("All authentication activities, whether successful or unsuccessful, must be recorded.");
    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Authentication activities logger'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Authentication failures log is enabled.');

      case CheckResult::FAIL:
        return $this->t('Authentication failures log is not enabled, <a href="/admin/config/security-review">Enable it now</a>');

      default:
        return $this->t("Unexpected result.");
    }
  }

}
