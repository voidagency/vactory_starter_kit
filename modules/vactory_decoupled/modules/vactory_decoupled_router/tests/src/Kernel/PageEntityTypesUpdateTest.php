<?php

namespace Drupal\Tests\vactory_decoupled_router\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the config of the page entity types: install and update 10001.
 *
 * The config schema is checked on each save.
 *
 * @group vactory_decoupled_router
 */
class PageEntityTypesUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'path_alias',
    'vactory_decoupled_router',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_handler')->loadInclude('vactory_decoupled_router', 'install');
  }

  /**
   * The list of the config.
   */
  protected function getList() {
    return $this->config('vactory_decoupled_router.settings')->get('page_entity_types');
  }

  /**
   * A new site gets the default list from the installed config.
   */
  public function testNewInstallHasTheDefaultList(): void {
    $this->installConfig(['vactory_decoupled_router']);
    $this->assertSame(['node'], $this->getList());
  }

  /**
   * The update creates the config on an existing site, once.
   */
  public function testUpdateCreatesTheDefaultList(): void {
    $this->assertTrue($this->config('vactory_decoupled_router.settings')->isNew());

    $message = (string) vactory_decoupled_router_update_10001();
    $this->assertSame(['node'], $this->getList());
    $this->assertStringContainsString('/admin/config/system/vactory_router/page-entity-types', $message);

    // A second run changes nothing.
    $message = (string) vactory_decoupled_router_update_10001();
    $this->assertSame(['node'], $this->getList());
    $this->assertStringContainsString('nothing to do', $message);
  }

  /**
   * The update keeps a list that already exists.
   *
   * @dataProvider providerExistingLists
   */
  public function testUpdateKeepsAnExistingList(array $existing): void {
    $this->config('vactory_decoupled_router.settings')
      ->set('page_entity_types', $existing)
      ->save();
    vactory_decoupled_router_update_10001();
    $this->assertSame($existing, $this->getList());
  }

  /**
   * Data provider for testUpdateKeepsAnExistingList().
   */
  public static function providerExistingLists(): array {
    return [
      'node and terms' => [['node', 'taxonomy_term']],
      'without node' => [['taxonomy_term']],
      'empty' => [[]],
    ];
  }

}
