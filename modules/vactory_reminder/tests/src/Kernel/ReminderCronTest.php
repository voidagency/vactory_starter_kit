<?php

namespace Drupal\Tests\vactory_reminder\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the cron of vactory_reminder outside Drush.
 *
 * @group vactory_reminder
 */
class ReminderCronTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'ultimate_cron',
    'vactory_reminder',
  ];

  /**
   * The cron runs the reminder queue from a web request or a system cron.
   */
  public function testCronRunsOutsideDrush(): void {
    $this->installConfig(['vactory_reminder']);
    $this->container->get('module_handler')->invoke('vactory_reminder', 'cron');
    // No exception: the queue ran (it is empty here).
    $this->addToAssertionCount(1);
  }

}
