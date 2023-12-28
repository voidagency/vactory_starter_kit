<?php

namespace Drupal\vactory_security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks whether user privileges changes log is enbaled.
 */
class UserPrivilegesChangesLog extends Check {

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
    return 'User privileges changes logs';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'user_privileges_log';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = [];
    $log_user_privileges_change = \Drupal::config('security_review.checks')->get('log_user_privileges_change');
    if (!$log_user_privileges_change) {
      $result = CheckResult::FAIL;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t("All activities or occasions where the user's privilege level changes should be recorded.");
    return [
      '#theme' => 'check_help',
      '#title' => $this->t("User's privilege level changes logger"),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t("User's privilege level changes log is enabled.");

      case CheckResult::FAIL:
        return $this->t("User's privilege level changes log is not enabled, <a href=\"/admin/config/security-review\">Enable it now</a>");

      default:
        return $this->t("Unexpected result.");
    }
  }

}
