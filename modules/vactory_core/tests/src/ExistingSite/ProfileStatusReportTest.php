<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Checks two errors of the status report that came from the profile.
 *
 * @group vactory_core
 */
class ProfileStatusReportTest extends ExistingSiteBase {

  /**
   * Admin Toolbar Links Access Filter is no longer installed.
   *
   * Its feature is part of Drupal core since 10.3, and the module reports an
   * error in the status report while it is installed.
   */
  public function testAdminToolbarLinksAccessFilterIsNotInstalled(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('admin_toolbar_links_access_filter'));
    $info = \Drupal::service('extension.list.profile')->getExtensionInfo('vactory_starter_kit');
    $this->assertNotContains('admin_toolbar_links_access_filter', $info['dependencies'] ?? []);
  }

  /**
   * The definition of the path field of files is installed.
   */
  public function testFilePathFieldIsInstalled(): void {
    $module_handler = \Drupal::moduleHandler();
    if (!$module_handler->moduleExists('file_entity') || !$module_handler->moduleExists('pathauto')) {
      $this->markTestSkipped('file_entity and pathauto are not both enabled.');
    }
    $changes = \Drupal::entityDefinitionUpdateManager()->getChangeSummary();
    $this->assertArrayNotHasKey('file', $changes, 'File fields need an update: ' . implode(' ', array_map('strval', $changes['file'] ?? [])));
  }

}
