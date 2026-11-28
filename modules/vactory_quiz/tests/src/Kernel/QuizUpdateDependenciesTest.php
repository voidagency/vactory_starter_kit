<?php

namespace Drupal\Tests\vactory_quiz\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the update enables the dependencies declared in 3.3.0.
 *
 * @group vactory_quiz
 */
class QuizUpdateDependenciesTest extends KernelTestBase {

  /**
   * A site from before 3.3.0: vactory_quiz without its new dependencies.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'node',
    'views',
    'vactory_quiz',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The modules of the profile are only discovered when the profile is the
    // install profile, as on the sites of the projects.
    $this->setInstallProfile('vactory_starter_kit');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'user']);
  }

  /**
   * The update enables the missing modules, then does nothing.
   */
  public function testUpdateEnablesMissingDependencies(): void {
    $module_handler = $this->container->get('module_handler');
    $this->assertFalse($module_handler->moduleExists('views_autocomplete_filters'));
    $this->assertFalse($module_handler->moduleExists('vactory_views'));

    $module_handler->loadInclude('vactory_quiz', 'install');
    vactory_quiz_update_10001();

    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('views_autocomplete_filters'));
    $this->assertTrue($module_handler->moduleExists('vactory_views'));
    $this->assertSame('The modules required by Vactory Quiz are already enabled.', (string) vactory_quiz_update_10001());
  }

}
