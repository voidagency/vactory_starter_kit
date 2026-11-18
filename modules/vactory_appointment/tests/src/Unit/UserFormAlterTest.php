<?php

namespace Drupal\Tests\vactory_appointment\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the adviser fields visibility in user forms.
 *
 * @group vactory_appointment
 */
class UserFormAlterTest extends UnitTestCase {

  /**
   * Alters a user form rendered with the given active theme.
   */
  protected function alterUserForm(string $active_theme): array {
    require_once dirname(__DIR__, 3) . '/vactory_appointment.module';
    $theme = $this->createMock(ActiveTheme::class);
    $theme->method('getName')->willReturn($active_theme);
    $theme_manager = $this->createMock(ThemeManagerInterface::class);
    $theme_manager->method('getActiveTheme')->willReturn($theme);
    $container = new ContainerBuilder();
    $container->set('theme.manager', $theme_manager);
    $container->set('config.factory', $this->getConfigFactoryStub([
      'system.theme' => ['admin' => 'claro', 'default' => 'vactory'],
    ]));
    \Drupal::setContainer($container);

    $form = [
      'field_adviser_holiday' => [],
      'field_adviser_appointments' => [],
    ];
    vactory_appointment_form_alter($form, new FormState(), 'user_form');
    return $form;
  }

  /**
   * Adviser fields are hidden in the back office (admin theme).
   */
  public function testHiddenInAdminTheme(): void {
    $form = $this->alterUserForm('claro');
    $this->assertFalse($form['field_adviser_holiday']['#access']);
    $this->assertFalse($form['field_adviser_appointments']['#access']);
  }

  /**
   * Adviser fields are untouched in the front theme.
   */
  public function testKeptInFrontTheme(): void {
    $form = $this->alterUserForm('vactory');
    $this->assertArrayNotHasKey('#access', $form['field_adviser_holiday']);
  }

}
