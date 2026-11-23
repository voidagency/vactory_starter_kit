<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * AddToAny script loaded only where share buttons are rendered.
 *
 * Tests vactory_core_page_attachments_alter() and
 * vactory_core_preprocess_addtoany_standard().
 *
 * @group vactory_core
 */
class AddToAnyAttachmentsTest extends UnitTestCase {

  /**
   * Library of the AddToAny module that loads its external script.
   */
  const LIBRARY = 'addtoany/addtoany.front';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 3) . '/vactory_core.module';
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->willReturn(FALSE);
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->getConfigFactoryStub([
      'addtoany.settings' => ['additional_js' => '', 'no_3p' => FALSE],
    ]));
    $container->set('module_handler', $module_handler);
    \Drupal::setContainer($container);
  }

  /**
   * The script added to every page by AddToAny is removed.
   */
  public function testPageAttachmentsRemoveLibrary(): void {
    $attachments = [
      '#attached' => [
        'html_head' => [
          [['#tag' => 'script'], 'addtoany-js'],
          [['#tag' => 'meta'], 'other'],
        ],
        'library' => ['core/drupal', self::LIBRARY],
      ],
    ];
    vactory_core_page_attachments_alter($attachments);
    $this->assertNotContains(self::LIBRARY, $attachments['#attached']['library']);
    $this->assertContains('core/drupal', $attachments['#attached']['library']);
    $this->assertSame(['other'], array_column($attachments['#attached']['html_head'], 1));
  }

  /**
   * The script and its configuration are attached with the share buttons.
   */
  public function testShareButtonsAttachLibrary(): void {
    $variables = [];
    vactory_core_preprocess_addtoany_standard($variables);
    $this->assertContains(self::LIBRARY, $variables['#attached']['library']);
    $this->assertSame(['addtoany-js'], array_column($variables['#attached']['html_head'], 1));
  }

  /**
   * The library name used above is declared by the installed AddToAny module.
   */
  public function testLibraryExistsInAddToAny(): void {
    $class = 'Drupal\addtoany\Plugin\Block\AddToAnyBlock';
    if (!class_exists($class)) {
      $this->markTestSkipped('The AddToAny module is not available.');
    }
    $module_path = dirname((new \ReflectionClass($class))->getFileName(), 4);
    $libraries = Yaml::decode(file_get_contents($module_path . '/addtoany.libraries.yml'));
    [, $name] = explode('/', self::LIBRARY, 2);
    $this->assertArrayHasKey($name, $libraries);
  }

}
