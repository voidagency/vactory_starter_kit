<?php

namespace Drupal\vactory_decoupled_breadcrumb\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Validate decoupled nodes breadcrumbs.
 *
 * @group vactory_decoupled
 */
class BreadcrumbTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($adminUser);
  }

  /**
   * Tester les breadcrumbs d’un nœud simple sans menu.
   */
  public function testBreadcrumbsSimplePage(): void {
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Breadcrumb Test Page',
      'status' => 1,
    ]);
    $langcode = $node->language()->getId();
    $this->setUpBreadcrumbConfig($langcode, TRUE, TRUE, 'Home');

    $breadcrumbs = $this->fetchNodeBreadcrumbs($node);
    $this->assertIsArray($breadcrumbs);
    $this->assertCount(2, $breadcrumbs);

    $this->assertBreadcrumbStructure($breadcrumbs[0], "/$langcode", 'Home', 0);

    $alias = \Drupal::service('path_alias.manager')->getAliasByPath("/node/{$node->id()}", $langcode);
    $this->assertBreadcrumbStructure($breadcrumbs[1], "/$langcode{$alias}", $node->getTitle(), 1);
  }

  /**
   * Tester les breadcrumbs pour un nœud lié à un élément de menu avec parent.
   */
  public function testBreadcrumbsNodeInMenuWithParent(): void {
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Child Page Test',
      'status' => 1,
    ]);
    $langcode = $node->language()->getId();
    $this->setUpBreadcrumbConfig($langcode, TRUE, TRUE, 'Home', ['main']);

    $parent = MenuLinkContent::create([
      'title' => 'Custom Parent',
      'menu_name' => 'main',
      'link' => ['uri' => 'route:<nolink>'],
      'expanded' => TRUE,
    ]);
    $parent->save();

    $child = MenuLinkContent::create([
      'title' => $node->getTitle(),
      'menu_name' => 'main',
      'link' => ['uri' => 'entity:node/' . $node->id()],
      'parent' => $parent->getPluginId(),
    ]);
    $child->save();

    $breadcrumbs = $this->fetchNodeBreadcrumbs($node);
    $this->assertIsArray($breadcrumbs);
    $this->assertCount(3, $breadcrumbs);

    $this->assertBreadcrumbStructure($breadcrumbs[0], "/$langcode", 'Home', 0);

    $this->assertArrayHasKey('url', $breadcrumbs[1]);
    $this->assertTrue(empty($breadcrumbs[1]['url']) || $breadcrumbs[1]['url'] === '#');
    $this->assertEquals('Custom Parent', $breadcrumbs[1]['text']);

    $alias = \Drupal::service('path_alias.manager')->getAliasByPath("/node/{$node->id()}", $langcode);
    $this->assertBreadcrumbStructure($breadcrumbs[2], "/$langcode{$alias}", $node->getTitle(), 2);

    $child->delete();
    $parent->delete();
  }

  /**
   * Tester les breadcrumbs basés sur la hiérarchie d’URL (alias).
   */
  public function testBreadcrumbsFromPath(): void {
    $node1 = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'First level',
      'status' => 1,
    ]);
    $node2 = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Second level',
      'status' => 1,
    ]);
    $langcode = $node1->language()->getId();

    $storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $storage->create([
      'path' => "/node/{$node1->id()}",
      'alias' => '/first-level',
      'langcode' => $langcode,
    ])->save();

    $storage->create([
      'path' => "/node/{$node2->id()}",
      'alias' => '/first-level/second-level',
      'langcode' => $langcode,
    ])->save();

    $this->setUpBreadcrumbConfig($langcode, TRUE, TRUE, 'Home');

    $breadcrumbs = $this->fetchNodeBreadcrumbs($node2);
    $this->assertCount(3, $breadcrumbs);

    $this->assertBreadcrumbStructure($breadcrumbs[0], "/$langcode", 'Home', 0);
    $this->assertBreadcrumbStructure($breadcrumbs[1], "/$langcode/first-level", 'First level', 1);
    $this->assertBreadcrumbStructure($breadcrumbs[2], "/$langcode/first-level/second-level", 'Second level', 2);
  }

  /**
   * Tester les breadcrumbs d'un seul nœud avec alias hiérarchique.
   */
  public function testBreadcrumbsFromPathSingleNodeWithHierarchy(): void {
    // Crée un nœud avec un alias profond : /first-level/second-level.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Second level',
      'status' => 1,
    ]);
    $langcode = $node->language()->getId();

    // Crée un alias simulant une hiérarchie.
    $storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $storage->create([
      'path' => "/node/{$node->id()}",
      'alias' => '/first-level/second-level',
      'langcode' => $langcode,
    ])->save();

    // Applique la configuration requise.
    $this->setUpBreadcrumbConfig($langcode, TRUE, TRUE, 'Home');

    // Récupère les breadcrumbs via JSON:API.
    $breadcrumbs = $this->fetchNodeBreadcrumbs($node);
    $this->assertCount(3, $breadcrumbs);

    // Assertion : Home.
    $this->assertBreadcrumbStructure($breadcrumbs[0], "/$langcode", 'Home', 0);

    // Assertion : First level (nolink).
    $this->assertBreadcrumbStructure($breadcrumbs[1], "#", 'First level', 1);

    // Assertion : Second level  (nolink).
    $this->assertBreadcrumbStructure($breadcrumbs[2], "#", 'Second level', 2);
  }

  /**
   * Configure les paramètres de breadcrumbs pour un test.
   */
  private function setUpBreadcrumbConfig(string $langcode, bool $showHome, bool $showCurrentPage, string $homeTitle, array $enabledMenus = []): void {
    $this->modifyConfigValue('vactory_decoupled_breadcrumb.settings', 'show_home', $showHome, $langcode);
    $this->modifyConfigValue('vactory_decoupled_breadcrumb.settings', 'show_current_page', $showCurrentPage, $langcode);
    $this->modifyConfigValue('vactory_decoupled_breadcrumb.settings', 'home_title', $homeTitle, $langcode);
    $this->modifyConfigValue('vactory_decoupled_breadcrumb.settings', 'enabled_menu', $enabledMenus, $langcode);
  }

  /**
   * Récupérer les breadcrumbs d’un nœud via JSON:API.
   */
  private function fetchNodeBreadcrumbs($node): array {
    $langcode = $node->language()->getId();
    $json = $this->fetchNodeJsonApi($node, $langcode);
    $this->assertArrayHasKey('internal_breadcrumb', $json['data']['attributes']);
    return $json['data']['attributes']['internal_breadcrumb'];
  }

  /**
   * Vérifie qu’un élément de breadcrumb possède bien la structure attendue.
   */
  private function assertBreadcrumbStructure(array $breadcrumb, string $expectedUrl, string $expectedText, int $index): void {
    $this->assertArrayHasKey('url', $breadcrumb, "Breadcrumb[{$index}] must contain 'url'.");
    $this->assertArrayHasKey('text', $breadcrumb, "Breadcrumb[{$index}] must contain 'text'.");
    $this->assertEquals($expectedUrl, $breadcrumb['url'], "Breadcrumb[{$index}] url mismatch.");
    $this->assertEquals($expectedText, $breadcrumb['text'], "Breadcrumb[{$index}] text mismatch.");
  }

}
