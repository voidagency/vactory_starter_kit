<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use Drupal\menu_link_content\Entity\MenuLinkContent;
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
   * The Twig function used by the header/footer templates still exists.
   */
  public function testTwigFunction(): void {
    $template = \Drupal::service('twig')->createTemplate(
      "{% set menu = simplify_menu('" . $this->menuId . "') %}{% for item in menu.menu_tree %}[{{ item.text }}:{{ item.submenu|length }}]{% endfor %}"
    );
    $this->assertSame('[Parent item:1]', $template->render([]));
  }

}
