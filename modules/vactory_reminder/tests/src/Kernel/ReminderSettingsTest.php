<?php

namespace Drupal\Tests\vactory_reminder\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\vactory_reminder\Form\VactoryReminderSettingsForm;

/**
 * Tests the settings of vactory_reminder against their schema.
 *
 * The config schema is checked strictly on every save.
 *
 * @group vactory_reminder
 */
class ReminderSettingsTest extends KernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['vactory_reminder']);
  }

  /**
   * The installed settings match the schema, with an empty list of consumers.
   */
  public function testInstalledSettings(): void {
    $config = $this->config('vactory_reminder.settings');
    $this->assertSame([], $config->get('reminder_consumers'));
    $this->assertSame(300, $config->get('reminder_lease_time'));
  }

  /**
   * The form stores one interval, or a list for a consumer on several lines.
   */
  public function testFormStoresConsumers(): void {
    $form_state = (new FormState())->setValues([
      'reminder_time_limit' => '120',
      'reminder_lease_time' => '300',
      'reminder_consumers' => implode(PHP_EOL, [
        'single|1 min',
        'multi|1 day',
        'multi|2 days',
        'multi|3 days',
      ]),
    ]);
    $this->container->get('form_builder')
      ->submitForm(VactoryReminderSettingsForm::class, $form_state);
    $this->assertSame([], $form_state->getErrors());

    $config = $this->config('vactory_reminder.settings');
    $this->assertSame([
      'single' => '1 min',
      'multi' => ['1 day', '2 days', '3 days'],
    ], $config->get('reminder_consumers'));
    // Saved as integers, as the schema says.
    $this->assertSame(120, $config->get('reminder_time_limit'));
  }

  /**
   * The update replaces the empty string installed before the schema.
   */
  public function testUpdateConvertsEmptyString(): void {
    // Written without the schema check, as on a site installed before it.
    $this->container->get('config.storage')->write('vactory_reminder.settings', [
      'reminder_lease_time' => 300,
      'reminder_time_limit' => 120,
      'reminder_consumers' => '',
    ]);
    $this->container->get('config.factory')->reset('vactory_reminder.settings');

    $this->container->get('module_handler')->loadInclude('vactory_reminder', 'install');
    vactory_reminder_update_10001();

    $this->assertSame([], $this->config('vactory_reminder.settings')->get('reminder_consumers'));
  }

}
