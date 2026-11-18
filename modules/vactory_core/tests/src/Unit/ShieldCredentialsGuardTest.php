<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;

/**
 * Shield cannot be enabled with empty Basic Auth credentials.
 *
 * @group vactory_core
 */
class ShieldCredentialsGuardTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 3) . '/vactory_core.module';
    require_once dirname(__DIR__, 3) . '/vactory_core.install';
    // Load the REQUIREMENT_* constants the way core does. Defining them here
    // would break core's later include of install.inc in the same process.
    include_once $this->root . '/core/includes/install.inc';
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Validates the Shield settings form with the given values.
   */
  protected function validate(bool $enabled, string $user, string $pass): FormState {
    $form_state = (new FormState())->setValues([
      'general' => ['shield_enable' => $enabled],
      'credentials' => [
        'credential_provider' => 'shield',
        'providers' => ['shield' => ['user' => $user, 'pass' => $pass]],
      ],
    ]);
    $form = [];
    vactory_core_shield_settings_validate($form, $form_state);
    return $form_state;
  }

  /**
   * Enabling Shield with an empty user or password is refused.
   */
  public function testFormRefusesEmptyCredentials(): void {
    $this->assertCount(2, $this->validate(TRUE, '', '')->getErrors());
    $this->assertCount(1, $this->validate(TRUE, 'admin', ' ')->getErrors());
    $this->assertCount(0, $this->validate(TRUE, 'admin', 's3cret')->getErrors());
    $this->assertCount(0, $this->validate(FALSE, '', '')->getErrors());
  }

  /**
   * The status report flags an enabled Shield without credentials.
   */
  public function testRequirements(): void {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->willReturn(TRUE);
    $container = \Drupal::getContainer();
    $container->set('module_handler', $module_handler);
    $container->set('config.factory', $this->getConfigFactoryStub([
      'shield.settings' => [
        'shield_enable' => TRUE,
        'credential_provider' => 'shield',
        'credentials' => ['shield' => ['user' => '', 'pass' => '']],
      ],
    ]));
    $this->assertArrayHasKey('vactory_core_shield_credentials', vactory_core_requirements('runtime'));
    $this->assertSame([], vactory_core_requirements('install'));
  }

}
