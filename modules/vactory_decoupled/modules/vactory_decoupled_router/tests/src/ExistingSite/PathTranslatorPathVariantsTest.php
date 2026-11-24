<?php

namespace Drupal\Tests\vactory_decoupled_router\ExistingSite;

use Drupal\pathauto\PathautoState;
use Drupal\redirect\Entity\Redirect;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\vactory_decoupled_router\Entity\Route;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Characterization of GET /api/router for variants of a path.
 *
 * Completes PathTranslatorTest: letter case, slashes, encodings, query string,
 * languages, redirect entities, entity types other than node, shapes of the
 * "path" parameter, HTTP methods and response headers.
 *
 * These tests describe the behaviour as it is. Some of it is questionable
 * (see the "@todo" notes); update the test with the behaviour.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled_router\Controller\PathTranslator
 * @group vactory_decoupled_router
 */
class PathTranslatorPathVariantsTest extends ExistingSiteBase {

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
   * Creates a published page with a fixed alias (pathauto skipped).
   *
   * @param string $alias
   *   The alias, with its leading slash.
   * @param array $values
   *   Extra node values.
   */
  protected function createPage(string $alias, array $values = []) {
    return $this->createNode($values + [
      'type' => 'vactory_page',
      'title' => 'Path variants ' . $this->randomMachineName(8),
      'status' => 1,
      'moderation_state' => 'published',
      'langcode' => 'en',
      'path' => ['alias' => $alias, 'pathauto' => PathautoState::SKIP],
    ]);
  }

  /**
   * A random lower case alias segment.
   */
  protected function randomSegment(): string {
    return 'pv-' . strtolower($this->randomMachineName(10));
  }

  /**
   * Creates a redirect entity removed at the end of the test.
   *
   * @param string $source
   *   The source path, without leading slash.
   * @param string $uri
   *   The target URI.
   * @param string $langcode
   *   The language of the redirect.
   */
  protected function createRedirect(string $source, string $uri, string $langcode = 'und'): Redirect {
    $redirect = Redirect::create([
      'redirect_source' => ['path' => $source],
      'redirect_redirect' => ['uri' => $uri],
      'status_code' => 301,
      'language' => $langcode,
    ]);
    $redirect->save();
    $this->markEntityForCleanup($redirect);
    return $redirect;
  }

  /**
   * Asserts the output is the given node, with a 200 and no redirect.
   */
  protected function assertNode($node, array $data, string $message = ''): void {
    $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data), $message);
    $this->assertSame(200, $data['status'], $message);
    $this->assertSame('node', $data['entity']['type'], $message);
    $this->assertSame($node->uuid(), $data['entity']['uuid'], $message);
    $this->assertStringContainsString($node->uuid(), $data['jsonapi']['individual'], $message);
  }

  /**
   * Asserts the output is the error page with a 404 and the given message.
   */
  protected function assertNotFound(array $data, string $message): void {
    $error = Route::load('error_page');
    $this->assertNotNull($error, 'System route error_page exists.');
    $this->assertSame(404, $data['status']);
    $this->assertSame($message, $data['message']);
    $this->assertSame(['_route' => 'error_page'], $data['system']);
    $this->assertSame('node', $data['entity']['type']);
    $this->assertSame(substr($error->getPath(), strlen('/node/')), $data['entity']['id']);
    $this->assertStringEndsWith('?routerStatus=404', $data['jsonapi']['individual']);
  }

  /**
   * Replaces the placeholders of a provider path.
   *
   * @param string $template
   *   A path with "{alias}" (the alias without its leading slash), "{ALIAS}"
   *   (the same in upper case) or "{nid}".
   * @param object $node
   *   The node.
   */
  protected function buildPath(string $template, $node): string {
    $alias = ltrim($node->get('path')->alias, '/');
    return strtr($template, [
      '{alias}' => $alias,
      '{ALIAS}' => strtoupper($alias),
      '{Alias}' => ucfirst($alias),
      '{nid}' => $node->id(),
    ]);
  }

  /**
   * Variants of an alias that resolve to the node, without any redirect.
   *
   * @todo Each variant is a second address of the same page (status 200, no
   *   redirect to the stored alias).
   *
   * @covers ::translate
   * @dataProvider providerAliasVariants
   */
  public function testAliasVariantResolvesToTheNode(string $template): void {
    $node = $this->createPage('/' . $this->randomSegment() . '/leaf');
    $path = $this->buildPath($template, $node);
    $this->assertNode($node, $this->translate($path), $path);
  }

  /**
   * Data provider for testAliasVariantResolvesToTheNode().
   */
  public static function providerAliasVariants(): array {
    return [
      'stored alias' => ['/{alias}'],
      'upper case' => ['/{ALIAS}'],
      'first letter in upper case' => ['/{Alias}'],
      'trailing slash' => ['/{alias}/'],
      'upper case and trailing slash' => ['/{ALIAS}/'],
      'no leading slash' => ['{alias}'],
      'language prefix' => ['/en/{alias}'],
      'language prefix and upper case' => ['/en/{ALIAS}'],
      'query string' => ['/{alias}?page=2'],
      'upper case and query string' => ['/{ALIAS}?page=2'],
      'fragment' => ['/{alias}#top'],
      'absolute URL' => ['https://example.com/{alias}'],
      'protocol relative URL' => ['//example.com/{alias}'],
    ];
  }

  /**
   * Variants of an alias or of /node/N that are not resolved.
   *
   * @covers ::translate
   * @dataProvider providerUnresolvedVariants
   */
  public function testUnresolvedVariantGivesTheErrorPage(string $template): void {
    $node = $this->createPage('/' . $this->randomSegment());
    $path = $this->buildPath($template, $node);
    $data = $this->translate($path);
    $this->assertNotFound($data, "No route found for $path");
    $this->assertArrayNotHasKey('redirect', $data);
  }

  /**
   * Data provider for testUnresolvedVariantGivesTheErrorPage().
   */
  public static function providerUnresolvedVariants(): array {
    return [
      'upper case language prefix' => ['/EN/{alias}'],
      'encoded slash' => ['/{alias}%2F'],
      'parent directory segment' => ['/../{alias}'],
      'current directory segment' => ['/./{alias}'],
      'segment before the alias' => ['/pv-other/{alias}'],
      'segment after the alias' => ['/{alias}/pv-other'],
      'two slashes only' => ['//'],
      'control characters' => ["/{alias}\r\nX-Pv: 1"],
    ];
  }

  /**
   * A path starting with two slashes and one segment gives the front page.
   *
   * The path is read as a URL: the first segment is taken as a host name.
   *
   * @todo The front page answers for any "//segment" path.
   *
   * @covers ::translate
   */
  public function testTwoLeadingSlashesGiveTheFrontPage(): void {
    $front = \Drupal::config('system.site')->get('page.front');
    $this->assertMatchesRegularExpression('#^/node/\d+$#', $front);
    $data = $this->translate('//' . $this->randomSegment());
    $this->assertSame(200, $data['status']);
    $this->assertSame(substr($front, strlen('/node/')), $data['entity']['id']);
    $this->assertArrayNotHasKey('redirect', $data);
  }

  /**
   * An alias with accents is found without its accents, and the reverse.
   *
   * The alias lookup follows the collation of the database (accent and case
   * insensitive on MySQL and MariaDB).
   *
   * @covers ::translate
   */
  public function testAliasWithAccents(): void {
    $segment = $this->randomSegment();
    $node = $this->createPage("/$segment-été");
    foreach (["/$segment-été", "/$segment-ete", "/$segment-ÉTÉ"] as $path) {
      $this->assertNode($node, $this->translate($path), $path);
    }
  }

  /**
   * An alias with a space or a plus sign.
   *
   * The value of "path" is parsed as a URL. A space is found as is; a plus
   * sign is read as a space, so the alias is found only when the plus sign is
   * encoded a second time.
   *
   * @covers ::translate
   */
  public function testAliasWithSpaceOrPlusSign(): void {
    $segment = $this->randomSegment();
    $spaced = $this->createPage("/$segment two");
    $this->assertNode($spaced, $this->translate("/$segment two"));
    $this->assertNode($spaced, $this->translate("/$segment%20two"));

    $plus = $this->createPage("/$segment+plus");
    $path = "/$segment+plus";
    $this->assertNotFound($this->translate($path), "No route found for $path");
    $this->assertNode($plus, $this->translate("/$segment%2Bplus"));
  }

  /**
   * Paths under /node/N that resolve to the node, without redirect.
   *
   * Only the exact "/node/N" form is redirected to the alias (see
   * PathTranslatorTest::testNodeByInternalPath()).
   *
   * @todo The administrative paths of a node answer like the node itself.
   *
   * @covers ::translate
   * @dataProvider providerNodePathVariants
   */
  public function testNodePathVariantResolvesToTheNode(string $template): void {
    $node = $this->createPage('/' . $this->randomSegment());
    $path = $this->buildPath($template, $node);
    $this->assertNode($node, $this->translate($path), $path);
  }

  /**
   * Data provider for testNodePathVariantResolvesToTheNode().
   */
  public static function providerNodePathVariants(): array {
    return [
      'upper case' => ['/NODE/{nid}'],
      'trailing slash' => ['/node/{nid}/'],
      'edit form' => ['/node/{nid}/edit'],
      'delete form' => ['/node/{nid}/delete'],
      'revisions' => ['/node/{nid}/revisions'],
    ];
  }

  /**
   * A path ending with /node/N that matches no node.
   *
   * The error page is returned with a 404, and with a redirect to the alias
   * of the error page itself.
   *
   * @todo A front that follows "redirect" first sends the visitor to the
   *   error page address instead of answering 404.
   *
   * @covers ::translate
   * @dataProvider providerUnknownNodePaths
   */
  public function testUnknownNodePathRedirectsToTheErrorPage(string $path): void {
    $error = Route::load('error_page');
    $this->assertNotNull($error);
    $error_node = \Drupal::entityTypeManager()->getStorage('node')
      ->load(substr($error->getPath(), strlen('/node/')));
    if (!$error_node->hasTranslation('en') || !$error_node->getTranslation('en')->get('path')->alias) {
      $this->markTestSkipped('The error page has no English alias on this site.');
    }
    $data = $this->translate($path);
    $this->assertNotFound($data, "No route found for $path");
    $this->assertSame([
      [
        'to' => '/en' . $error_node->getTranslation('en')->get('path')->alias,
        'status' => 301,
        'from' => $path,
      ],
    ], $data['redirect']);
  }

  /**
   * Data provider for testUnknownNodePathRedirectsToTheErrorPage().
   */
  public static function providerUnknownNodePaths(): array {
    return [
      'unknown node id' => ['/node/987654321'],
      'unknown path ending like a node path' => ['/pv-unknown/node/1'],
    ];
  }

  /**
   * Languages: an alias only exists in the language of its translation.
   *
   * @covers ::translate
   */
  public function testAliasOfAnotherLanguage(): void {
    $en_alias = '/' . $this->randomSegment();
    $fr_alias = '/' . $this->randomSegment();
    $node = $this->createPage($en_alias);
    $node->addTranslation('fr', [
      'title' => 'Path variants FR ' . $this->randomMachineName(6),
      'status' => 1,
      'moderation_state' => 'published',
      'path' => ['alias' => $fr_alias, 'pathauto' => PathautoState::SKIP],
    ])->save();

    // The alias of a language is unknown to the endpoint of another one.
    $this->assertNotFound($this->translate($en_alias, 'fr'), "No route found for $en_alias");
    $this->assertNotFound($this->translate($fr_alias, 'en'), "No route found for $fr_alias");

    // The letter case of the alias is ignored in each language.
    $this->assertNode($node, $this->translate(strtoupper($fr_alias), 'fr'));
    $this->assertNode($node, $this->translate('/fr' . strtoupper($fr_alias), 'fr'));
    $this->assertNode($node, $this->translate(strtoupper($en_alias), 'en'));

    // A node without translation in the language of the endpoint: its alias
    // is unknown, /node/N gives the node in its own language.
    $single = $this->createPage('/' . $this->randomSegment());
    $single_alias = $single->get('path')->alias;
    $this->assertNotFound($this->translate($single_alias, 'fr'), "No route found for $single_alias");
    $data = $this->translate('/node/' . $single->id(), 'fr');
    $this->assertNode($single, $data);
    $this->assertSame($single->label(), $data['entity']['label']);
  }

  /**
   * A taxonomy term is resolved like a node: by /taxonomy/term/N or alias.
   *
   * @covers ::translate
   */
  public function testTaxonomyTerm(): void {
    $vocabularies = Vocabulary::loadMultiple();
    if (!$vocabularies) {
      $this->markTestSkipped('No vocabulary on this site.');
    }
    $vocabulary = reset($vocabularies);
    $alias = '/' . $this->randomSegment();
    $term = $this->createTerm($vocabulary, [
      'name' => 'Path variants term ' . $this->randomMachineName(6),
      'langcode' => 'en',
      'path' => ['alias' => $alias, 'pathauto' => PathautoState::SKIP],
    ]);
    $resource = \Drupal::service('jsonapi.resource_type.repository')
      ->get('taxonomy_term', $vocabulary->id())
      ->getTypeName();

    foreach (['/taxonomy/term/' . $term->id(), $alias, strtoupper($alias)] as $path) {
      $data = $this->translate($path);
      $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data), $path);
      $this->assertSame([
        'type' => 'taxonomy_term',
        'bundle' => $vocabulary->id(),
        'label' => $term->label(),
        'uuid' => $term->uuid(),
        'id' => (string) $term->id(),
      ], $data['entity'], $path);
      $this->assertSame($resource, $data['jsonapi']['resourceName'], $path);
      $this->assertStringEndsWith('/' . $term->uuid(), $data['jsonapi']['individual'], $path);
      $this->assertSame(200, $data['status'], $path);
    }

    // An unpublished term gives the error page.
    $term->setUnpublished()->save();
    $path = '/taxonomy/term/' . $term->id();
    $this->assertNotFound(
      $this->translate($path),
      "A route has been found for $path , but it has no entity information!"
    );
  }

  /**
   * Routes without entity give the error page.
   *
   * @covers ::translate
   * @dataProvider providerRoutesWithoutEntity
   */
  public function testRouteWithoutEntityGivesTheErrorPage(string $path): void {
    $this->assertNotFound(
      $this->translate($path),
      "A route has been found for $path , but it has no entity information!"
    );
  }

  /**
   * Data provider for testRouteWithoutEntityGivesTheErrorPage().
   */
  public static function providerRoutesWithoutEntity(): array {
    return [
      'administration' => ['/admin'],
      'administration with language prefix' => ['/en/admin'],
      'the router itself' => ['/api/router'],
      'node creation' => ['/node/add'],
    ];
  }

  /**
   * Redirect entities: which forms of the source path are matched.
   *
   * @covers ::translate
   */
  public function testRedirectSourceVariants(): void {
    $node = $this->createPage('/' . $this->randomSegment());
    $alias = $node->get('path')->alias;
    $source = $this->randomSegment();
    $this->createRedirect($source, 'internal:/node/' . $node->id());

    // The letter case of the source is ignored; "from" is the requested path.
    $upper = '/' . strtoupper($source);
    $this->assertSame([
      'status' => '301',
      'redirect' => [
        ['from' => $upper, 'to' => '/en' . $alias, 'status' => '301'],
      ],
    ], $this->translate($upper));

    // Not matched: trailing slash, language prefix, query string.
    foreach (["/$source/", "/en/$source", "/$source?page=2"] as $path) {
      $this->assertNotFound($this->translate($path), "No route found for $path");
    }
  }

  /**
   * Redirect entities: language, chain, query string of the target.
   *
   * @covers ::translate
   */
  public function testRedirectTargets(): void {
    $node = $this->createPage('/' . $this->randomSegment());
    $alias = $node->get('path')->alias;

    // A redirect of one language is unknown to the other languages.
    $french = $this->randomSegment();
    $this->createRedirect($french, 'internal:/node/' . $node->id(), 'fr');
    $this->assertNotFound($this->translate("/$french"), "No route found for /$french");
    $data = $this->translate("/$french", 'fr');
    $this->assertSame('301', $data['status']);
    $this->assertSame("/$french", $data['redirect'][0]['from']);

    // A chain of redirects gives the last target, in one step.
    $first = $this->randomSegment();
    $second = $this->randomSegment();
    $this->createRedirect($first, "internal:/$second");
    $this->createRedirect($second, 'internal:/node/' . $node->id());
    $data = $this->translate("/$first");
    $this->assertSame([
      ['from' => "/$first", 'to' => '/en' . $alias, 'status' => '301'],
    ], $data['redirect']);

    // Query string and fragment of the target are kept.
    $with_query = $this->randomSegment();
    $this->createRedirect($with_query, "internal:$alias?page=2#top");
    $data = $this->translate("/$with_query");
    $this->assertSame('/en' . $alias . '?page=2#top', $data['redirect'][0]['to']);

    // The target is not checked: an unpublished node is still a target.
    $draft = $this->createPage('/' . $this->randomSegment(), [
      'status' => 0,
      'moderation_state' => 'draft',
    ]);
    $to_draft = $this->randomSegment();
    $this->createRedirect($to_draft, 'internal:/node/' . $draft->id());
    $data = $this->translate("/$to_draft");
    $this->assertSame('301', $data['status']);
    $this->assertSame('/en' . $draft->get('path')->alias, $data['redirect'][0]['to']);
    $this->assertArrayNotHasKey('entity', $data);
  }

  /**
   * A redirect entity wins over an existing alias with the same path.
   *
   * @covers ::translate
   */
  public function testRedirectWinsOverAlias(): void {
    $target = $this->createPage('/' . $this->randomSegment());
    $node = $this->createPage('/' . $this->randomSegment());
    $alias = $node->get('path')->alias;
    $this->createRedirect(ltrim($alias, '/'), 'internal:/node/' . $target->id());
    $data = $this->translate($alias);
    $this->assertSame(['status', 'redirect'], array_keys($data));
    $this->assertSame('/en' . $target->get('path')->alias, $data['redirect'][0]['to']);
  }

  /**
   * Values of "path" refused before any lookup.
   *
   * @covers ::translate
   * @dataProvider providerRefusedPathParameters
   */
  public function testRefusedPathParameter(array $query, int $status, string $message): void {
    $this->drupalGet('/api/router', ['query' => $query]);
    $this->assertSession()->statusCodeEquals($status);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertSame(['message' => $message], $data);
  }

  /**
   * Data provider for testRefusedPathParameter().
   */
  public static function providerRefusedPathParameters(): array {
    $empty = 'Unable to translate empty path. Please send a ?path query string parameter with your request.';
    $array = 'Input value "path" contains a non-scalar value.';
    return [
      'empty string' => [['path' => ''], 404, $empty],
      'zero' => [['path' => '0'], 404, $empty],
      'another parameter only' => [['pv' => '/node/1'], 404, $empty],
      'list' => [['path' => ['/node/1']], 400, $array],
      'nested list' => [['path' => ['a' => ['b' => '/node/1']]], 400, $array],
    ];
  }

  /**
   * A very long path gives the error page, not an error.
   *
   * @covers ::translate
   */
  public function testVeryLongPath(): void {
    $path = '/' . str_repeat('pv-long/', 250) . 'end';
    $this->assertNotFound($this->translate($path), "No route found for $path");
  }

  /**
   * Only GET (and HEAD) in JSON is routed.
   *
   * @covers ::translate
   */
  public function testMethodsAndFormat(): void {
    $url = $this->buildUrl('/api/router', ['query' => ['path' => '/']]);
    $client = $this->getSession()->getDriver()->getClient();
    foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
      $client->request($method, $url);
      $this->assertSame(405, $client->getInternalResponse()->getStatusCode(), $method);
    }

    $query = ['path' => '/', '_format' => 'xml'];
    $this->drupalGet('/api/router', ['query' => $query]);
    $this->assertSession()->statusCodeEquals(406);

    // The Accept header is ignored: JSON is always returned.
    $query = ['path' => '/'];
    $this->drupalGet('/api/router', ['query' => $query], ['Accept' => 'text/html']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame('application/json', $this->getSession()->getResponseHeader('Content-Type'));
  }

  /**
   * The response is not cacheable by Drupal or by a shared cache.
   *
   * Every status (200, 404, redirect) is computed for the current user on
   * each request.
   *
   * @covers ::translate
   */
  public function testResponseIsNotCacheable(): void {
    $node = $this->createPage('/' . $this->randomSegment());
    $paths = [$node->get('path')->alias, '/' . $this->randomSegment()];
    foreach ($paths as $path) {
      // Twice: the second answer does not come from the page cache.
      $this->translate($path);
      $this->translate($path);
      $cache_control = $this->getSession()->getResponseHeader('Cache-Control');
      $this->assertStringContainsString('private', $cache_control, $path);
      $this->assertStringContainsString('no-cache', $cache_control, $path);
      $this->assertStringNotContainsString('public', $cache_control, $path);
      $this->assertNotSame('HIT', $this->getSession()->getResponseHeader('X-Drupal-Cache'), $path);
      $this->assertNotSame('HIT', $this->getSession()->getResponseHeader('X-Drupal-Dynamic-Cache'), $path);
    }
  }

}
