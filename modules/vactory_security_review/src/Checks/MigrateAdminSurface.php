<?php

namespace Drupal\vactory_security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\user\Entity\Role;

/**
 * Checks that the migrate administration surface is restricted.
 *
 * Migrations can run arbitrary process plugins, and configuration import can
 * create them, so both are equivalent to code execution for whoever can reach
 * them. This verifies that the migrate UI is denied over HTTP (Drush only) and
 * that no non-admin role can import configuration or manage migrations.
 */
class MigrateAdminSurface extends Check {

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
    return 'Migrate administration surface';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'migrate_admin_surface';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $findings = [];

    if (!\Drupal::moduleHandler()->moduleExists('vactory_admin_hardening')) {
      $findings[] = 'The "vactory_admin_hardening" module is not installed: the migrate administration UI is reachable over HTTP. Migrations should be run through Drush only.';
    }

    // No non-admin role should be able to import configuration or manage
    // migrations. The admin role (uid 1 / "is_admin") is expected to have them.
    $dangerous = ['import configuration', 'administer migrations'];
    foreach (Role::loadMultiple() as $role) {
      if ($role->isAdmin()) {
        continue;
      }
      foreach ($dangerous as $permission) {
        if ($role->hasPermission($permission)) {
          $findings[] = sprintf('Role "%s" holds the "%s" permission.', $role->id(), $permission);
        }
      }
    }

    $result = empty($findings) ? CheckResult::SUCCESS : CheckResult::FAIL;
    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('Migrations run arbitrary process plugins and configuration import can create them, so both grant code execution to whoever can reach them. The migrate UI must be denied over HTTP (use Drush), and only the administrator role should hold "import configuration" or "administer migrations".');
    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Migrate administration surface'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('The migrate UI is restricted to Drush and no non-admin role can import configuration or manage migrations.');

      case CheckResult::FAIL:
        return $this->t('The migrate administration surface is exposed. Review the findings below.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
