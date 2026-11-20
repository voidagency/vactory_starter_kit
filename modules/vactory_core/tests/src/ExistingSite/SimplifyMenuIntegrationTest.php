<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\NodeInterface;
use Drupal\system\Entity\Menu;
use Drupal\vactory_core\MenuItems;
use Drupal\vactory_core\MenuRoleLinkTreeManipulator;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Our use of drupal/simplify_menu (and the menu tree manipulators).
 *
 * VactoryCoreServiceProvider replaces simplify_menu.menu_items and
 * menu.default_tree_manipulators with our classes; our dependencies are
 * injected through method calls so that upstream constructor changes do not
 * break the container. The header/footer templates call simplify_menu().
 *
 * @group vactory_core
 * @group dependencies
 */
class SimplifyMenuIntegrationTest extends ExistingSiteBase {

  /**
   * Machine name of the test menu.
   *
   * @var string
   */
  protected string $menuId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->menuId = 'test-simplify-' . strtolower($this->randomMachineName(6));
    $menu = Menu::create([
      'id' => $this->menuId,
      'label' => 'Simplify menu test',
    ]);
    $menu->save();
    $parent = MenuLinkContent::create([
      'title' => 'Parent item',
      'link' => [
        'uri' => 'internal:/',
        'options' => ['attributes' => ['class' => ['header-link']]],
      ],
      'menu_name' => $this->menuId,
      'weight' => 0,
    ]);
    $parent->save();
    $child = MenuLinkContent::create([
      'title' => 'Child item',
      'link' => ['uri' => 'internal:/user/login'],
      'menu_name' => $this->menuId,
      'parent' => $parent->getPluginId(),
    ]);
    $child->save();
    // Shown to administrators only (menu_role_show_role, checked by our
    // tree manipulator and our MenuItems).
    $restricted = MenuLinkContent::create([
      'title' => 'Admins only',
      'link' => ['uri' => 'internal:/'],
      'menu_name' => $this->menuId,
      'weight' => 5,
      'menu_role_show_role' => ['administrator'],
    ]);
    $restricted->save();
    // Children first: deleting the menu deletes its links.
    foreach ([$child, $restricted, $parent, $menu] as $entity) {
      $this->markEntityForCleanup($entity);
    }
  }

  /**
   * Our services are built (constructor arguments still match).
   */
  public function testServicesAreOurs(): void {
    $this->assertInstanceOf(MenuItems::class, \Drupal::service('simplify_menu.menu_items'));
    $this->assertInstanceOf(MenuRoleLinkTreeManipulator::class, \Drupal::service('menu.default_tree_manipulators'));
  }

  /**
   * The simplified tree has the structure our templates read.
   */
  public function testMenuTree(): void {
    $tree = \Drupal::service('simplify_menu.menu_items')->getMenuTree($this->menuId);
    $this->assertArrayHasKey('menu_tree', $tree);
    $parent = $tree['menu_tree'][0];
    $this->assertSame('Parent item', $parent['text']);
    foreach (['url', 'active', 'active_trail'] as $key) {
      $this->assertArrayHasKey($key, $parent);
    }
    $this->assertSame('Child item', $parent['submenu'][0]['text'] ?? NULL);
    // Keys read by the vactory_header templates.
    $this->assertSame(['header-link'], $parent['options']['attributes']['class'] ?? NULL);
    $this->assertArrayHasKey('menu_role_show_role', $parent['fields']);
  }

  /**
   * A link restricted to a role is hidden from other users (anonymous here).
   */
  public function testRoleRestrictedLinkIsHidden(): void {
    $this->assertTrue(\Drupal::currentUser()->isAnonymous());
    $tree = \Drupal::service('simplify_menu.menu_items')->getMenuTree($this->menuId);
    $this->assertNotContains('Admins only', array_column($tree['menu_tree'], 'text'));
  }

  /**
   * Flattens a simplified tree into [text => url], all levels included.
   */
  protected function flatten(array $items): array {
    $links = [];
    foreach ($items as $item) {
      $links[(string) $item['text']] = $item['url'];
      $links += $this->flatten($item['submenu'] ?? []);
    }
    return $links;
  }

  /**
   * Adds a link to the test menu.
   */
  protected function addLink(string $title, string $uri, array $values = []): MenuLinkContent {
    $link = MenuLinkContent::create($values + [
      'title' => $title,
      'link' => ['uri' => $uri],
      'menu_name' => $this->menuId,
      'weight' => 10,
    ]);
    $link->save();
    $this->markEntityForCleanup($link);
    return $link;
  }

  /**
   * Creates a published or unpublished page.
   */
  protected function createPage(bool $published): NodeInterface {
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => ($published ? 'Published' : 'Unpublished') . ' page ' . $this->randomMachineName(8),
      'status' => (int) $published,
    ]);
    // With content moderation the state decides, not "status".
    if ($node->hasField('moderation_state')) {
      $node->set('moderation_state', $published ? 'published' : 'draft');
      $node->save();
    }
    $this->assertSame($published, $node->isPublished());
    return $node;
  }

  /**
   * A disabled link is not part of the simplified tree, at any level.
   */
  public function testDisabledLinksAreHidden(): void {
    $parent = $this->addLink('Enabled parent', 'internal:/');
    $this->addLink('Disabled top link', 'internal:/', ['enabled' => FALSE]);
    $this->addLink('Disabled child', 'internal:/user/login', [
      'enabled' => FALSE,
      'parent' => $parent->getPluginId(),
    ]);

    $tree = \Drupal::service('simplify_menu.menu_items')->getMenuTree($this->menuId);
    $links = $this->flatten($tree['menu_tree']);
    $this->assertArrayHasKey('Enabled parent', $links);
    $this->assertArrayNotHasKey('Disabled top link', $links);
    $this->assertArrayNotHasKey('Disabled child', $links);
  }

  /**
   * A link the current user cannot access is not part of the simplified tree.
   *
   * Simplify_menu 3.3 skips these links like MenuLinkTree::build() does; our
   * override of simplifyLinks() has to do the same (Redmine #71363).
   */
  public function testInaccessibleLinksAreHidden(): void {
    $unpublished = $this->createPage(FALSE);
    $published = $this->createPage(TRUE);
    $parent = $this->addLink('Accessible parent', 'entity:node/' . $published->id());
    $this->addLink('Unpublished top link', 'entity:node/' . $unpublished->id());
    $this->addLink('Unpublished child', 'entity:node/' . $unpublished->id(), ['parent' => $parent->getPluginId()]);
    $this->addLink('Administration link', 'internal:/admin/config');

    $this->assertTrue(\Drupal::currentUser()->isAnonymous());
    $tree = \Drupal::service('simplify_menu.menu_items')->getMenuTree($this->menuId);
    $links = $this->flatten($tree['menu_tree']);

    $this->assertArrayHasKey('Accessible parent', $links);
    $hidden = [
      'Unpublished top link',
      'Unpublished child',
      'Administration link',
    ];
    foreach ($hidden as $title) {
      $this->assertArrayNotHasKey($title, $links);
    }
    // Neither under another label.
    $this->assertNotContains($unpublished->toUrl()->toString(), $links);
    $this->assertArrayNotHasKey('Inaccessible', $links);
    $this->assertCount(2, $tree['menu_tree'], 'Only the two accessible top-level links are left.');
  }

  /**
   * A user who can access the target of a link gets it.
   */
  public function testAccessibleLinksAreKept(): void {
    $unpublished = $this->createPage(FALSE);
    $this->addLink('Unpublished top link', 'entity:node/' . $unpublished->id());

    $editor = $this->createUser(['bypass node access']);
    $this->setCurrentUser($editor);
    $tree = \Drupal::service('simplify_menu.menu_items')->getMenuTree($this->menuId);
    $this->assertArrayHasKey('Unpublished top link', $this->flatten($tree['menu_tree']));
  }

  /**
   * The Twig function used by the header/footer templates still exists.
   */
  public function testTwigFunction(): void {
    $template = \Drupal::service('twig')->createTemplate(
      "{% set menu = simplify_menu('" . $this->menuId . "') %}{% for item in menu.menu_tree %}[{{ item.text }}:{{ item.submenu|length }}]{% endfor %}"
    );
    $this->assertSame('[Parent item:1]', $template->render([]));
  }

}
