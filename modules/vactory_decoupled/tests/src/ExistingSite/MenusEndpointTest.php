<?php

namespace Drupal\Tests\vactory_decoupled\ExistingSite;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\NodeInterface;
use Drupal\system\Entity\Menu;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the menu endpoint read by the decoupled frontend.
 *
 * The frontend does not use simplify_menu: it reads this endpoint, built
 * with the core menu link tree. These tests pin what it returns, so a change
 * to the server-side menus (simplify_menu, Redmine #71363) cannot alter the
 * frontend menus unnoticed.
 *
 * @group vactory_decoupled
 */
class MenusEndpointTest extends ExistingSiteBase {

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
    $this->menuId = 'test-endpoint-' . strtolower($this->randomMachineName(6));
    $menu = Menu::create([
      'id' => $this->menuId,
      'label' => 'Menu endpoint test',
    ]);
    $menu->save();
    $this->markEntityForCleanup($menu);
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
   * Requests the endpoint and returns the titles, children included.
   *
   * @return string[]
   *   Link titles, in a flat list.
   */
  protected function menuTitles(): array {
    $this->drupalGet('/_menus', ['query' => ['menu_name' => $this->menuId]]);
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertIsArray($data);
    $this->assertSame($data['items'], json_decode($data['json'], TRUE));
    return $this->flatten($data['items']);
  }

  /**
   * Flattens the items of the endpoint into a list of titles.
   */
  protected function flatten(array $items): array {
    $titles = [];
    foreach ($items as $item) {
      $titles[] = (string) $item['title'];
      $titles = array_merge($titles, $this->flatten($item['below'] ?? []));
    }
    return $titles;
  }

  /**
   * An anonymous visitor only gets the links they can access.
   */
  public function testAnonymousGetsAccessibleLinksOnly(): void {
    $published = $this->createPage(TRUE);
    $unpublished = $this->createPage(FALSE);
    $parent = $this->addLink('Accessible parent', 'entity:node/' . $published->id());
    $this->addLink('Accessible child', 'entity:node/' . $published->id(), [
      'parent' => $parent->getPluginId(),
    ]);
    $this->addLink('Unpublished child', 'entity:node/' . $unpublished->id(), [
      'parent' => $parent->getPluginId(),
    ]);
    $this->addLink('Unpublished top link', 'entity:node/' . $unpublished->id());
    $this->addLink('Administration link', 'internal:/admin/config');
    $this->addLink('Disabled link', 'entity:node/' . $published->id(), [
      'enabled' => 0,
    ]);

    $titles = $this->menuTitles();
    $this->assertSame(['Accessible parent', 'Accessible child'], $titles);
    $content = $this->getSession()->getPage()->getContent();
    $this->assertStringNotContainsString($unpublished->label(), $content);
  }

  /**
   * An empty or unknown menu answers an empty list.
   */
  public function testEmptyMenu(): void {
    $this->assertSame([], $this->menuTitles());
  }

}
