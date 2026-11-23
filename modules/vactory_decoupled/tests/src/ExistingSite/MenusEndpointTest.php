<?php

namespace Drupal\Tests\vactory_decoupled\ExistingSite;

use Drupal\Core\Session\AccountInterface;
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
   * Logs in with a one-time login link.
   *
   * The drupalLogin() method cannot be used here: on a decoupled site the
   * user page it lands on may redirect to the front, which is not there
   * during the tests.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account created by createUser().
   */
  protected function loginAs(AccountInterface $account): void {
    $this->drupalGet(user_pass_reset_url($account) . '/login', [
      'query' => ['destination' => 'session/token'],
    ]);
    $cookie = \Drupal::service('session_configuration')->getOptions(\Drupal::request())['name'];
    // @see \Drupal\Tests\UiHelperTrait::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie($cookie);
    $this->assertTrue($this->drupalUserIsLoggedIn($account));
    $this->loggedInUser = $account;
  }

  /**
   * Requests the endpoint and returns the decoded answer.
   *
   * @param array $query
   *   Query parameters added to the menu name.
   *
   * @return array
   *   The decoded JSON answer.
   */
  protected function menuData(array $query = []): array {
    $this->drupalGet('/_menus', [
      'query' => ['menu_name' => $this->menuId] + $query,
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertIsArray($data);
    $this->assertSame(['items', 'json'], array_keys($data));
    $this->assertSame($data['items'], json_decode($data['json'], TRUE));
    return $data;
  }

  /**
   * Requests the endpoint and returns the titles, children included.
   *
   * @param array $query
   *   Query parameters added to the menu name.
   *
   * @return string[]
   *   Link titles, in a flat list.
   */
  protected function menuTitles(array $query = []): array {
    return $this->flatten($this->menuData($query)['items']);
  }

  /**
   * Fills the test menu with accessible and restricted links.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The published page and the unpublished page the links point to.
   */
  protected function addMixedLinks(): array {
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
    return [$published, $unpublished];
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
    [, $unpublished] = $this->addMixedLinks();

    $titles = $this->menuTitles();
    $this->assertSame(['Accessible parent', 'Accessible child'], $titles);
    $content = $this->getSession()->getPage()->getContent();
    $this->assertStringNotContainsString($unpublished->label(), $content);
  }

  /**
   * A user gets the links their permissions give access to.
   */
  public function testUserGetsLinksAllowedByPermissions(): void {
    $this->addMixedLinks();
    $this->loginAs($this->createUser([
      'access content',
      'bypass node access',
    ]));

    $this->assertSame([
      'Accessible parent',
      'Accessible child',
      'Unpublished child',
      'Unpublished top link',
    ], $this->menuTitles());
  }

  /**
   * Each item has an id, a title, options and a URL; children are nested.
   */
  public function testTreeStructure(): void {
    $page = $this->createPage(TRUE);
    $parent = $this->addLink('Second parent', 'entity:node/' . $page->id(), [
      'weight' => 20,
    ]);
    $child = $this->addLink('Child', 'entity:node/' . $page->id(), [
      'parent' => $parent->getPluginId(),
    ]);
    $first = $this->addLink('First link', 'entity:node/' . $page->id(), [
      'weight' => 5,
    ]);

    $items = $this->menuData()['items'];
    $this->assertSame(['First link', 'Second parent'], array_column($items, 'title'));
    $this->assertSame([$first->uuid(), $parent->uuid()], array_column($items, 'id'));
    $this->assertSame(['id', 'title', 'options', 'url'], array_keys($items[0]));
    $this->assertSame(['id', 'title', 'options', 'url', 'below'], array_keys($items[1]));
    $this->assertSame($page->toUrl()->toString(), $items[0]['url']);
    $this->assertIsArray($items[0]['options']);
    $this->assertCount(1, $items[1]['below']);
    $this->assertSame($child->uuid(), $items[1]['below'][0]['id']);
    $this->assertSame('Child', $items[1]['below'][0]['title']);
    $this->assertArrayNotHasKey('below', $items[1]['below'][0]);
  }

  /**
   * The max_depth parameter limits the levels returned.
   */
  public function testMaxDepth(): void {
    $this->addMixedLinks();
    $this->assertSame(['Accessible parent'], $this->menuTitles(['max_depth' => 1]));
  }

  /**
   * A request without menu_name answers "not found".
   */
  public function testMissingMenuName(): void {
    $this->drupalGet('/_menus');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * The "ac" parameter is ignored: the answer is the same as without it.
   *
   * @dataProvider providerIgnoredParameter
   */
  public function testAccessParameterIsIgnored(string $value): void {
    [, $unpublished] = $this->addMixedLinks();
    $expected = $this->menuData();

    $this->assertSame($expected, $this->menuData(['ac' => $value]));
    $content = $this->getSession()->getPage()->getContent();
    $this->assertStringNotContainsString($unpublished->label(), $content);
    $this->assertStringNotContainsString('Administration link', $content);
  }

  /**
   * The "ac" parameter is ignored for a logged-in user too.
   *
   * @dataProvider providerIgnoredParameter
   */
  public function testAccessParameterIsIgnoredForUser(string $value): void {
    $this->addMixedLinks();
    $this->loginAs($this->createUser(['access content']));
    $expected = $this->menuData();

    $this->assertSame(
      ['Accessible parent', 'Accessible child'],
      $this->flatten($expected['items'])
    );
    $this->assertSame($expected, $this->menuData(['ac' => $value]));
  }

  /**
   * Values of the "ac" parameter.
   */
  public static function providerIgnoredParameter(): array {
    return [
      'zero' => ['0'],
      'empty' => [''],
      'one' => ['1'],
    ];
  }

  /**
   * An empty or unknown menu answers an empty list.
   */
  public function testEmptyMenu(): void {
    $this->assertSame([], $this->menuTitles());
  }

}
