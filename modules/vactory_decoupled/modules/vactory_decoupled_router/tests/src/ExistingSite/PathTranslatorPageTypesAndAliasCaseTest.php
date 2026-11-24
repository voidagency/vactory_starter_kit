<?php

namespace Drupal\Tests\vactory_decoupled_router\ExistingSite;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Site\Settings;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\pathauto\PathautoState;
use Drupal\redirect\Entity\Redirect;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\vactory_decoupled_router\Controller\PathTranslator;
use Drupal\vactory_decoupled_router\Entity\Route;
use Symfony\Component\HttpFoundation\Request;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * GET /api/router: page entity types and variants of the alias.
 *
 * - Only the page entity types (config vactory_decoupled_router.settings,
 *   node by default, or the "vactory_decoupled_router_page_entity_types"
 *   setting) are resolved; the others give the error page with a 404.
 * - An alias requested with another letter case, other accents or a trailing
 *   slash gives a permanent redirect to the stored alias.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled_router\Controller\PathTranslator
 * @group vactory_decoupled_router
 */
class PathTranslatorPageTypesAndAliasCaseTest extends ExistingSiteBase {

  /**
   * Name of the config and of the setting of the page entity types.
   */
  protected const CONFIG = 'vactory_decoupled_router.settings';
  protected const SETTING = 'vactory_decoupled_router_page_entity_types';

  /**
   * The config before the test (NULL when it does not exist).
   *
   * @var array|null
   */
  protected $originalConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $config = \Drupal::config(self::CONFIG);
    $this->originalConfig = $config->isNew() ? NULL : $config->getRawData();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $config = \Drupal::configFactory()->getEditable(self::CONFIG);
    if ($this->originalConfig === NULL) {
      $config->delete();
    }
    else {
      $config->setData($this->originalConfig)->save();
    }
    parent::tearDown();
  }

  /**
   * Requests the router and returns the decoded JSON.
   *
   * @param string $path
   *   The path to translate.
   * @param string $langcode
   *   Language of the router endpoint itself (its URL prefix).
   */
  protected function translate(string $path, string $langcode = 'en'): array {
    $this->drupalGet('/api/router', [
      'query' => ['path' => $path],
      'language' => \Drupal::languageManager()->getLanguage($langcode),
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertIsArray($data, 'The router answers JSON.');
    return $data;
  }

  /**
   * Calls the controller in-process, as anonymous, with extra settings.
   *
   * @param string $path
   *   The path to translate.
   * @param array $settings
   *   Settings added to the ones of the site for the call.
   */
  protected function translateWithSettings(string $path, array $settings): array {
    $original = Settings::getAll();
    $switcher = \Drupal::service('account_switcher');
    $switcher->switchTo(new AnonymousUserSession());
    new Settings($settings + $original);
    try {
      $request = Request::create('/api/router', 'GET', ['path' => $path]);
      $response = \Drupal::classResolver(PathTranslator::class)->translate($request);
    }
    finally {
      new Settings($original);
      $switcher->switchBack();
    }
    return json_decode($response->getContent(), TRUE);
  }

  /**
   * Saves the list of page entity types in the config.
   */
  protected function setConfiguredTypes(array $entity_types): void {
    \Drupal::configFactory()->getEditable(self::CONFIG)
      ->set('page_entity_types', $entity_types)
      ->save();
  }

  /**
   * Creates a published page with a fixed alias (pathauto skipped).
   *
   * @param string|null $alias
   *   The alias, with its leading slash; NULL for a node without alias.
   * @param array $values
   *   Extra node values.
   */
  protected function createPage(?string $alias, array $values = []) {
    return $this->createNode($values + [
      'type' => 'vactory_page',
      'title' => 'Alias case ' . $this->randomMachineName(8),
      'status' => 1,
      'moderation_state' => 'published',
      'langcode' => 'en',
      'path' => array_filter(['alias' => $alias]) + ['pathauto' => PathautoState::SKIP],
    ]);
  }

  /**
   * A random lower case alias segment.
   */
  protected function randomSegment(): string {
    return 'ac-' . strtolower($this->randomMachineName(10));
  }

  /**
   * Replaces "{s}" and "{S}" by the segment, in lower and upper case.
   */
  protected function fill(string $template, string $segment): string {
    return strtr($template, ['{s}' => $segment, '{S}' => strtoupper($segment)]);
  }

  /**
   * Asserts the output is the node with a permanent redirect to its alias.
   *
   * The redirect target, requested in turn, has to be the node without any
   * redirect: no loop, no chain.
   *
   * @param object $node
   *   The node.
   * @param string $from
   *   The requested path.
   * @param string $alias
   *   The stored alias, target of the redirect.
   * @param string $langcode
   *   Language of the router endpoint.
   */
  protected function assertRedirectToAlias($node, string $from, string $alias, string $langcode = 'en'): void {
    $data = $this->translate($from, $langcode);
    $to = "/$langcode" . UrlHelper::encodePath($alias);
    $this->assertSame(['entity', 'jsonapi', 'status', 'redirect'], array_keys($data), $from);
    $this->assertSame($node->uuid(), $data['entity']['uuid'], $from);
    $this->assertSame(301, $data['status'], $from);
    // The status of the redirect is a string: the front reads the redirect as
    // permanent only when it is strictly equal to "301".
    $this->assertSame([
      ['to' => $to, 'status' => '301', 'from' => $from],
    ], $data['redirect'], $from);
    // The target as the front requests it (language prefix removed), then as
    // it is written in the answer.
    $this->assertNoRedirect($node, $this->translate($alias, $langcode), "$from -> $alias");
    $this->assertNoRedirect($node, $this->translate($to, $langcode), "$from -> $to");
  }

  /**
   * Asserts the output is the node with a 200 and no redirect.
   */
  protected function assertNoRedirect($node, array $data, string $message = ''): void {
    $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data), $message);
    $this->assertSame($node->uuid(), $data['entity']['uuid'], $message);
    $this->assertSame(200, $data['status'], $message);
  }

  /**
   * Asserts the output is the error page with a 404, without redirect.
   */
  protected function assertNoPage(array $data, string $path, ?string $message = NULL): void {
    $error = Route::load('error_page');
    $this->assertSame(['entity', 'jsonapi', 'status', 'message', 'system'], array_keys($data), $path);
    $this->assertSame(404, $data['status'], $path);
    $this->assertSame($message ?? "No decoupled page for $path", $data['message'], $path);
    $this->assertSame(['_route' => 'error_page'], $data['system'], $path);
    $this->assertSame('node', $data['entity']['type'], $path);
    $this->assertSame(substr($error->getPath(), strlen('/node/')), $data['entity']['id'], $path);
    $this->assertStringEndsWith('?routerStatus=404', $data['jsonapi']['individual'], $path);
  }

  /**
   * Asserts the output is the given term, with a 200 and no redirect.
   */
  protected function assertTerm($term, array $data, string $message = ''): void {
    $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data), $message);
    $this->assertSame(200, $data['status'], $message);
    $this->assertSame('taxonomy_term', $data['entity']['type'], $message);
    $this->assertSame($term->uuid(), $data['entity']['uuid'], $message);
  }

  /**
   * A term of the first vocabulary of the site, with an alias.
   */
  protected function createTermWithAlias(string $alias) {
    $vocabularies = Vocabulary::loadMultiple();
    if (!$vocabularies) {
      $this->markTestSkipped('No vocabulary on this site.');
    }
    return $this->createTerm(reset($vocabularies), [
      'name' => 'Alias case term ' . $this->randomMachineName(6),
      'langcode' => 'en',
      'path' => ['alias' => $alias, 'pathauto' => PathautoState::SKIP],
    ]);
  }

  /**
   * An entity type out of the list gives the error page, without its data.
   *
   * @covers ::translate
   */
  public function testEntityTypeOutOfTheList(): void {
    $alias = '/' . $this->randomSegment();
    $term = $this->createTermWithAlias($alias);
    $paths = [
      '/taxonomy/term/' . $term->id(),
      '/taxonomy/term/' . $term->id() . '/edit',
      $alias,
      strtoupper($alias),
    ];
    foreach ($paths as $path) {
      $data = $this->translate($path);
      $this->assertNoPage($data, $path);
      $this->assertStringNotContainsString($term->uuid(), json_encode($data), $path);
    }
  }

  /**
   * The list comes from the config: default value, then a type added.
   *
   * @covers ::translate
   */
  public function testEntityTypeAddedByConfig(): void {
    $alias = '/' . $this->randomSegment();
    $term = $this->createTermWithAlias($alias);
    $node = $this->createPage('/' . $this->randomSegment());
    $path = '/taxonomy/term/' . $term->id();

    // Without the config (site not updated yet): node only.
    \Drupal::configFactory()->getEditable(self::CONFIG)->delete();
    $this->assertNoPage($this->translate($path), $path);
    $this->assertNoRedirect($node, $this->translate($node->get('path')->alias));

    // The default value of the config.
    $this->setConfiguredTypes(['node']);
    $this->assertNoPage($this->translate($path), $path);

    // The term type is added: the term is resolved, by path and by alias.
    $this->setConfiguredTypes(['node', 'taxonomy_term']);
    foreach ([$path, $alias] as $requested) {
      $this->assertTerm($term, $this->translate($requested), $requested);
    }
    $this->assertNoRedirect($node, $this->translate($node->get('path')->alias));

    // No redirect to the stored alias for an entity type other than node.
    $this->assertTerm($term, $this->translate(strtoupper($alias)));

    // An unpublished term of a page type: no entity, as before.
    $term->setUnpublished()->save();
    $this->assertNoPage(
      $this->translate($path),
      $path,
      "A route has been found for $path , but it has no entity information!"
    );
  }

  /**
   * Only the canonical address of an entity of an added type is resolved.
   *
   * The rule is the one of the nodes: the other routes of the entity give the
   * error page, without redirect and without its data.
   *
   * @covers ::translate
   */
  public function testOnlyCanonicalPathOfAddedType(): void {
    $alias = '/' . $this->randomSegment();
    $term = $this->createTermWithAlias($alias);
    $canonical = '/taxonomy/term/' . $term->id();
    $this->setConfiguredTypes(['node', 'taxonomy_term']);

    foreach ([$canonical, "/en$canonical", $alias, "/en$alias"] as $path) {
      $this->assertTerm($term, $this->translate($path), $path);
    }

    $paths = [
      "$canonical/edit",
      "/en$canonical/edit",
      "$canonical/delete",
      "$canonical/",
      '/TAXONOMY/TERM/' . $term->id(),
      '/taxonomy/term/0' . $term->id(),
    ];
    foreach ($paths as $path) {
      $data = $this->translate($path);
      $this->assertNoPage($data, $path);
      $this->assertArrayNotHasKey('redirect', $data, $path);
      $json = json_encode($data);
      $this->assertStringNotContainsString($term->uuid(), $json, $path);
      $this->assertStringNotContainsString($term->label(), $json, $path);
    }

    // The same answer for an unpublished term: the path is refused before
    // the entity is inspected.
    $term->setUnpublished()->save();
    $this->assertNoPage($this->translate("$canonical/edit"), "$canonical/edit");
  }

  /**
   * The setting of settings.php, when defined, replaces the config.
   *
   * @covers ::translate
   */
  public function testEntityTypeListFromSettings(): void {
    $alias = '/' . $this->randomSegment();
    $term = $this->createTermWithAlias($alias);
    $path = '/taxonomy/term/' . $term->id();

    // Not defined: the config is used.
    $this->setConfiguredTypes(['node']);
    $this->assertNoPage($this->translateWithSettings($path, []), $path);

    // The term type is added by the setting.
    $settings = [self::SETTING => ['node', 'taxonomy_term']];
    foreach ([$path, $alias] as $requested) {
      $this->assertTerm($term, $this->translateWithSettings($requested, $settings), $requested);
    }

    // The setting wins over a config that has the term type.
    $this->setConfiguredTypes(['node', 'taxonomy_term']);
    $this->assertTerm($term, $this->translateWithSettings($path, []));
    $settings = [self::SETTING => ['node']];
    $this->assertNoPage($this->translateWithSettings($path, $settings), $path);

    // A list without node: a node path gives the error page, which is still
    // a node; an unknown path keeps its own message.
    $node = $this->createPage('/' . $this->randomSegment());
    $node_alias = $node->get('path')->alias;
    $settings = [self::SETTING => ['taxonomy_term']];
    $data = $this->translateWithSettings($node_alias, $settings);
    $this->assertNoPage($data, $node_alias);
    $this->assertNotSame($node->uuid(), $data['entity']['uuid']);
    $unknown = '/' . $this->randomSegment();
    $data = $this->translateWithSettings($unknown, $settings);
    $this->assertNoPage($data, $unknown, "No route found for $unknown");
  }

  /**
   * An entity type without publication status: the error page, not an error.
   *
   * The entity type is checked before the status of the entity is read.
   *
   * @covers ::translate
   */
  public function testEntityTypeWithoutStatusKey(): void {
    if (!\Drupal::moduleHandler()->moduleExists('shortcut')) {
      $this->markTestSkipped('The shortcut module is not installed.');
    }
    $storage = \Drupal::entityTypeManager()->getStorage('shortcut');
    $shortcut = $storage->create([
      'shortcut_set' => 'default',
      'title' => 'Alias case ' . $this->randomMachineName(6),
      'link' => ['uri' => 'internal:/admin'],
    ]);
    $shortcut->save();
    $this->markEntityForCleanup($shortcut);
    $this->assertEmpty($shortcut->getEntityType()->getKey('status'));

    $path = '/admin/config/user-interface/shortcut/link/' . $shortcut->id();
    $data = $this->translate($path);
    $this->assertNoPage($data, $path);
    $this->assertStringNotContainsString($shortcut->uuid(), json_encode($data));
  }

  /**
   * A config entity route: the error page, as a type out of the list.
   *
   * @covers ::translate
   */
  public function testConfigEntityRoute(): void {
    $path = '/admin/structure/types/manage/vactory_page';
    $this->assertNoPage($this->translate($path), $path);
  }

  /**
   * A variant of the alias: permanent redirect to the stored alias.
   *
   * @covers ::translate
   * @dataProvider providerRedirectedVariants
   */
  public function testVariantRedirectsToTheStoredAlias(string $stored, string $requested): void {
    $segment = $this->randomSegment();
    $alias = $this->fill($stored, $segment);
    $node = $this->createPage($alias);
    $this->assertRedirectToAlias($node, $this->fill($requested, $segment), $alias);
  }

  /**
   * Data provider for testVariantRedirectsToTheStoredAlias().
   */
  public static function providerRedirectedVariants(): array {
    return [
      'upper case' => ['/{s}', '/{S}'],
      'one letter' => ['/{s}/leaf', '/{s}/Leaf'],
      'trailing slash' => ['/{s}', '/{s}/'],
      'upper case and trailing slash' => ['/{s}', '/{S}/'],
      'several segments' => ['/{s}/a/b', '/{S}/A/B'],
      'stored in upper case' => ['/{S}/Leaf', '/{s}/leaf'],
      'stored in mixed case' => ['/{S}/leaf', '/{s}/LEAF'],
      'no leading slash, upper case' => ['/{s}', '{S}'],
      'language prefix and upper case' => ['/{s}', '/en/{S}'],
      'language prefix and trailing slash' => ['/{s}', '/en/{s}/'],
      'upper case and query string' => ['/{s}', '/{S}?page=2'],
      'upper case and fragment' => ['/{s}', '/{S}#top'],
      'accent, upper case' => ['/{s}-été', '/{S}-ÉTÉ'],
      'accent, ASCII letters in upper case' => ['/{s}-été', '/{S}-été'],
      'accent, one accented letter in upper case' => ['/{s}-été', '/{s}-Été'],
      'accent removed' => ['/{s}-été', '/{s}-ete'],
      'accent added' => ['/{s}-ete', '/{s}-été'],
      'accent stored in upper case' => ['/{s}-ÉTÉ', '/{s}-été'],
      'cyrillic, upper case' => ['/{s}-привет', '/{s}-ПРИВЕТ'],
      'space, upper case' => ['/{s} two', '/{S} TWO'],
    ];
  }

  /**
   * Forms of the stored alias that give the node without redirect.
   *
   * @covers ::translate
   * @dataProvider providerVariantsWithoutRedirect
   */
  public function testStoredAliasIsNotRedirected(string $stored, string $requested): void {
    $segment = $this->randomSegment();
    $node = $this->createPage($this->fill($stored, $segment));
    $path = $this->fill($requested, $segment);
    $this->assertNoRedirect($node, $this->translate($path), $path);
  }

  /**
   * Data provider for testStoredAliasIsNotRedirected().
   */
  public static function providerVariantsWithoutRedirect(): array {
    return [
      'stored alias' => ['/{s}', '/{s}'],
      'stored alias in upper case' => ['/{S}', '/{S}'],
      'stored alias with accents' => ['/{s}-été', '/{s}-été'],
      'stored alias with accents, encoded' => ['/{s}-été', '/{s}-%C3%A9t%C3%A9'],
      'no leading slash' => ['/{s}', '{s}'],
      'language prefix' => ['/{s}', '/en/{s}'],
      'query string' => ['/{s}', '/{s}?page=2'],
    ];
  }

  /**
   * Each language redirects to its own alias.
   *
   * @covers ::translate
   */
  public function testTranslationVariant(): void {
    $segment = $this->randomSegment();
    // The two aliases differ only by their letter case.
    $node = $this->createPage("/$segment");
    $fr_alias = '/' . ucfirst($segment);
    $node->addTranslation('fr', [
      'title' => 'Alias case FR ' . $this->randomMachineName(6),
      'status' => 1,
      'moderation_state' => 'published',
      'path' => ['alias' => $fr_alias, 'pathauto' => PathautoState::SKIP],
    ])->save();

    $this->assertNoRedirect($node, $this->translate("/$segment", 'en'));
    $this->assertRedirectToAlias($node, $fr_alias, "/$segment", 'en');
    $this->assertNoRedirect($node, $this->translate($fr_alias, 'fr'));
    $this->assertRedirectToAlias($node, "/$segment", $fr_alias, 'fr');
    // With the language prefix in the path.
    $this->assertRedirectToAlias($node, "/fr/$segment", $fr_alias, 'fr');
  }

  /**
   * A node without translation in the language of the request.
   *
   * Its alias is known to this language only when it is language neutral; the
   * redirect then stays in the language of the request.
   *
   * @covers ::translate
   */
  public function testNodeNotTranslatedInTheRequestLanguage(): void {
    $segment = $this->randomSegment();
    $node = $this->createPage(NULL);
    $this->assertFalse($node->hasTranslation('fr'));
    $alias = PathAlias::create([
      'path' => '/node/' . $node->id(),
      'alias' => "/$segment",
      'langcode' => 'und',
    ]);
    $alias->save();

    try {
      $this->assertNoRedirect($node, $this->translate("/$segment", 'fr'));
      $upper = '/' . strtoupper($segment);
      $this->assertRedirectToAlias($node, $upper, "/$segment", 'fr');
      $this->assertRedirectToAlias($node, $upper, "/$segment", 'en');
    }
    finally {
      // Before the node is removed.
      $alias->delete();
    }
  }

  /**
   * Cases where the letter case of the path changes nothing.
   *
   * @covers ::translate
   */
  public function testNoCaseRedirectForOtherAnswers(): void {
    // The /node/N redirect keeps its form: status 200 and a redirect entry
    // whose status is an integer.
    $node = $this->createPage('/' . $this->randomSegment());
    $alias = $node->get('path')->alias;
    $data = $this->translate('/node/' . $node->id());
    $this->assertSame(200, $data['status']);
    $from = '/node/' . $node->id();
    $this->assertSame([
      ['to' => '/en' . $alias, 'status' => 301, 'from' => $from],
    ], $data['redirect']);

    // A node without alias. The other paths of a node are not its address:
    // the error page, without redirect.
    $bare = $this->createPage(NULL);
    $this->assertNoRedirect($bare, $this->translate('/node/' . $bare->id()));
    $paths = [
      '/NODE/' . $bare->id(),
      '/NODE/' . $node->id(),
      '/node/' . $node->id() . '/edit',
    ];
    foreach ($paths as $path) {
      $this->assertNoPage($this->translate($path), $path);
    }

    // An unpublished node: the error page, without redirect.
    $draft = $this->createPage('/' . $this->randomSegment(), [
      'status' => 0,
      'moderation_state' => 'draft',
    ]);
    $data = $this->translate(strtoupper($draft->get('path')->alias));
    $this->assertSame(404, $data['status']);
    $this->assertArrayNotHasKey('redirect', $data);

    // A redirect entity wins, with its own status.
    $source = $this->randomSegment();
    $redirect = Redirect::create([
      'redirect_source' => ['path' => $source],
      'redirect_redirect' => ['uri' => 'internal:/node/' . $node->id()],
      'status_code' => 302,
      'language' => 'und',
    ]);
    $redirect->save();
    $this->markEntityForCleanup($redirect);
    $data = $this->translate('/' . strtoupper($source));
    $this->assertSame('302', $data['status']);
    $this->assertCount(1, $data['redirect']);

    // A system route with a parameter: the node of the route, no redirect.
    $base = '/' . $this->randomSegment();
    $route = Route::create([
      'id' => 'ac_test_' . strtolower($this->randomMachineName(8)),
      'label' => 'Alias case test route',
      'path' => '/node/' . $node->id(),
      'alias' => $base . '/{param1}',
    ]);
    $route->save();
    $this->markEntityForCleanup($route);
    $data = $this->translate($base . '/Stuff');
    $this->assertSame(200, $data['status']);
    $this->assertArrayNotHasKey('redirect', $data);
    $this->assertSame(['param1' => 'Stuff'], $data['system']['_query']);

    // The front page.
    foreach (['/', '/en'] as $path) {
      $data = $this->translate($path);
      $this->assertSame(200, $data['status'], $path);
      $this->assertArrayNotHasKey('redirect', $data, $path);
    }
  }

}
