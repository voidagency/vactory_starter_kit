<?php

namespace Drupal\Tests\vactory_sondage\Kernel;

/**
 * Tests a sondage on a site where vactory_reminder is not enabled.
 *
 * The module declares vactory_reminder: a site gets it with vactory_sondage
 * and cannot uninstall it alone. A site where it is missing anyway (module
 * installed without its dependencies) could not save any sondage: the save
 * ended with a "non-existent service" error. The sondage is now saved, and
 * the missing closing is logged.
 *
 * @group vactory_sondage
 */
class SondageWithoutReminderTest extends SondageKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'block_content',
    'vactory_sondage',
  ];

  /**
   * A sondage is saved, and the closing that cannot be scheduled is logged.
   */
  public function testSondageIsSavedWithoutReminder(): void {
    $this->assertFalse($this->container->get('module_handler')->moduleExists('vactory_reminder'));

    $sondage = $this->createSondage(3600);

    $this->assertNotEmpty($sondage->id());
    $this->assertSame('1', $sondage->get('field_sondage_status')->value);
    $this->assertCount(1, $this->logged);
    $this->assertStringContainsString('vactory_reminder', $this->logged[0]);
  }

  /**
   * The update counts no scheduled sondage when it cannot schedule one.
   */
  public function testUpdateSchedulesNothingWithoutReminder(): void {
    $this->createSondage(3600);
    $this->logged = [];

    $this->container->get('module_handler')->loadInclude('vactory_sondage', 'install');
    $message = (string) vactory_sondage_update_10001();

    $this->assertStringContainsString('0 open sondages scheduled', $message);
    $this->assertCount(1, $this->logged);
  }

}
