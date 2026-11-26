<?php

namespace Drupal\Tests\vactory_core\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tinypng\Form\SettingsForm;

/**
 * TinyPNG, required by the profile: its settings are saved.
 *
 * Catches a version of the module whose settings form or configuration keys
 * change (the profile moved from 1.x to 2.x).
 *
 * @group vactory_core
 */
class TinypngSettingsIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'file', 'image', 'tinypng'];

  /**
   * TinyPNG 2.0.2 ships tinypng.settings without a schema for it.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = ['tinypng.settings'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['tinypng']);
  }

  /**
   * Submitting the settings form stores the values.
   */
  public function testSettingsAreSaved(): void {
    $form_state = (new FormState())->setValues([
      'api_key' => 'test-key-not-a-secret',
      'on_upload' => TRUE,
      'upload_method' => 'download',
      'image_action' => TRUE,
    ]);
    $this->container->get('form_builder')->submitForm(SettingsForm::class, $form_state);

    $this->assertSame([], $form_state->getErrors());
    $config = $this->config('tinypng.settings');
    $this->assertSame('test-key-not-a-secret', $config->get('api_key'));
    $this->assertTrue($config->get('on_upload'));
    $this->assertSame('download', $config->get('upload_method'));
    $this->assertTrue($config->get('image_action'));
  }

}
