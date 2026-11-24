<?php

namespace Drupal\Tests\vactory_decoupled_router\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_decoupled_router\Controller\PathTranslator;

/**
 * Where the list of page entity types of the router comes from.
 *
 * Config, replaced by the setting of settings.php when it is defined, then
 * altered by hook_vactory_decoupled_router_page_entity_types_alter().
 *
 * @coversDefaultClass \Drupal\vactory_decoupled_router\Controller\PathTranslator
 * @group vactory_decoupled_router
 */
class PathTranslatorPageEntityTypesTest extends UnitTestCase {

  /**
   * Computes the list with the given config, setting and hook.
   *
   * @param array|null $config
   *   Value of "page_entity_types" in the config; NULL when it is missing.
   * @param mixed $setting
   *   Value of the setting of settings.php; NULL when it is not defined.
   * @param callable|null $hook
   *   An implementation of the alter hook.
   */
  protected function getPageEntityTypes(?array $config, $setting, ?callable $hook = NULL): array {
    $name = 'vactory_decoupled_router_page_entity_types';
    new Settings($setting === NULL ? [] : [$name => $setting]);
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->expects($this->once())
      ->method('alter')
      ->willReturnCallback(function ($type, &$data) use ($name, $hook) {
        $this->assertSame($name, $type);
        if ($hook) {
          $hook($data);
        }
      });
    $config_factory = $this->getConfigFactoryStub([
      'vactory_decoupled_router.settings' => array_filter(['page_entity_types' => $config], 'is_array'),
    ]);

    $class = new \ReflectionClass(PathTranslator::class);
    $controller = $class->newInstanceWithoutConstructor();
    $services = [
      'configFactory' => $config_factory,
      'moduleHandler' => $module_handler,
    ];
    foreach ($services as $property => $service) {
      $reflection = $class->getProperty($property);
      $reflection->setAccessible(TRUE);
      $reflection->setValue($controller, $service);
    }
    $method = $class->getMethod('getPageEntityTypes');
    $method->setAccessible(TRUE);
    return $method->invoke($controller);
  }

  /**
   * Config, then setting.
   *
   * @covers ::getPageEntityTypes
   * @dataProvider providerSources
   */
  public function testConfigThenSetting(?array $config, $setting, array $expected): void {
    $this->assertSame($expected, $this->getPageEntityTypes($config, $setting));
  }

  /**
   * Data provider for testConfigThenSetting().
   */
  public static function providerSources(): array {
    $terms = ['node', 'taxonomy_term'];
    return [
      'nothing: node' => [NULL, NULL, ['node']],
      'config' => [$terms, NULL, $terms],
      'empty config' => [[], NULL, []],
      'setting without config' => [NULL, $terms, $terms],
      'setting replaces config' => [$terms, ['node'], ['node']],
      'empty setting replaces config' => [$terms, [], []],
      'setting with one type' => [['node'], 'taxonomy_term', ['taxonomy_term']],
      'values that are not strings' => [['node', 12, NULL], NULL, ['node']],
    ];
  }

  /**
   * The hook alters the list last, whatever its source.
   *
   * @covers ::getPageEntityTypes
   * @dataProvider providerHookSources
   */
  public function testHookAltersTheList(?array $config, $setting, array $expected): void {
    $hook = function (array &$entity_types) {
      $entity_types[] = 'my_entity';
      $entity_types = array_diff($entity_types, ['taxonomy_term']);
    };
    $this->assertSame($expected, $this->getPageEntityTypes($config, $setting, $hook));
  }

  /**
   * Data provider for testHookAltersTheList().
   */
  public static function providerHookSources(): array {
    $terms = ['node', 'taxonomy_term'];
    return [
      'default' => [NULL, NULL, ['node', 'my_entity']],
      'config' => [$terms, NULL, ['node', 'my_entity']],
      'setting' => [['node'], $terms, ['node', 'my_entity']],
    ];
  }

}
