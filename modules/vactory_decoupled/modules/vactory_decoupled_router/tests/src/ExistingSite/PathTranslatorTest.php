<?php

namespace Drupal\Tests\vactory_decoupled_router\ExistingSite;

use Drupal\Core\Session\AccountInterface;
use Drupal\pathauto\PathautoState;
use Drupal\redirect\Entity\Redirect;
use Drupal\vactory_decoupled_router\Controller\PathTranslator;
use Drupal\vactory_decoupled_router\Entity\Route;
use Symfony\Component\HttpFoundation\Request;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Characterization of PathTranslator::translate() through GET /api/router.
 *
 * Pins the current JSON output, status codes and headers before a refactor.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled_router\Controller\PathTranslator
 * @group vactory_decoupled_router
 */
class PathTranslatorTest extends ExistingSiteBase {

  /**
   * Requests the router and returns the decoded JSON.
   *
   * @param string $path
   *   The path to translate.
   * @param string $langcode
   *   Language of the router endpoint itself (its URL prefix).
   * @param array $query
   *   Extra query parameters.
   */
  protected function translate(string $path, string $langcode = 'en', array $query = []): array {
    $this->drupalGet('/api/router', [
      'query' => ['path' => $path] + $query,
      'language' => \Drupal::languageManager()->getLanguage($langcode),
    ]);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertIsArray($data, 'The router answers JSON.');
    return $data;
  }

  /**
   * Calls the controller in-process as the given account.
   */
  protected function translateAs(AccountInterface $account, string $path, array $query = []): array {
    $switcher = \Drupal::service('account_switcher');
    $switcher->switchTo($account);
    try {
      $request = Request::create('/api/router', 'GET', ['path' => $path] + $query);
      $response = \Drupal::classResolver(PathTranslator::class)->translate($request);
    }
    finally {
      $switcher->switchBack();
    }
    $this->assertSame(200, $response->getStatusCode());
    return json_decode($response->getContent(), TRUE);
  }

  /**
   * Creates a published page with a fixed alias (pathauto skipped).
   */
  protected function createPage(array $values = []) {
    $alias = '/pt-' . strtolower($this->randomMachineName(10));
    return $this->createNode($values + [
      'type' => 'vactory_page',
      'title' => 'Path translator ' . $this->randomMachineName(8),
      'status' => 1,
      'moderation_state' => 'published',
      'langcode' => 'en',
      'path' => ['alias' => $alias, 'pathauto' => PathautoState::SKIP],
    ]);
  }

  /**
   * Loads the node behind a system route (vactory_route config entity).
   */
  protected function systemRouteNode(string $id) {
    $route = Route::load($id);
    $this->assertNotNull($route, "System route $id exists.");
    $this->assertMatchesRegularExpression('#^/node/\d+$#', $route->getPath());
    $nid = substr($route->getPath(), strlen('/node/'));
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $this->assertNotNull($node);
    return $node;
  }

  /**
   * Asserts the "entity" and "jsonapi" keys describe the node.
   */
  protected function assertNodeOutput($node, array $data, string $individual_suffix = '', string $langcode = 'en', ?string $label = NULL): void {
    $this->assertSame([
      'type' => 'node',
      'bundle' => $node->bundle(),
      'label' => $label ?? $node->label(),
      'uuid' => $node->uuid(),
      'id' => (string) $node->id(),
    ], $data['entity']);
    $resource = 'node--' . $node->bundle();
    $this->assertSame($resource, $data['jsonapi']['resourceName']);
    $this->assertStringEndsWith(
      "/$langcode/api/node/{$node->bundle()}/{$node->uuid()}$individual_suffix",
      $data['jsonapi']['individual']
    );
    $this->assertMatchesRegularExpression('#^https?://#', $data['jsonapi']['individual'], 'The individual URL is absolute.');
  }

  /**
   * Asserts the output is the error page with the given status and message.
   */
  protected function assertErrorPage(array $data, int $status, string $message): void {
    $error = $this->systemRouteNode('error_page');
    $this->assertSame(['entity', 'jsonapi', 'status', 'message', 'system'], array_keys($data));
    $this->assertNodeOutput($error, $data, '?routerStatus=' . $status);
    $this->assertSame($status, $data['status']);
    $this->assertSame($message, $data['message']);
    $this->assertSame(['_route' => 'error_page'], $data['system']);
  }

  /**
   * A node requested by its alias.
   *
   * @covers ::translate
   */
  public function testNodeByAlias(): void {
    $node = $this->createPage();
    $alias = $node->get('path')->alias;
    $data = $this->translate($alias);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame('application/json', $this->getSession()->getResponseHeader('Content-Type'));
    $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data));
    $this->assertNodeOutput($node, $data);
    $this->assertSame(200, $data['status']);
  }

  /**
   * A node requested by /node/N redirects (301 in the body) to its alias.
   *
   * @covers ::translate
   */
  public function testNodeByInternalPath(): void {
    $node = $this->createPage();
    $alias = $node->get('path')->alias;
    $path = '/node/' . $node->id();
    $data = $this->translate($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(['entity', 'jsonapi', 'status', 'redirect'], array_keys($data));
    $this->assertNodeOutput($node, $data);
    $this->assertSame(200, $data['status']);
    $this->assertSame([
      ['to' => '/en' . $alias, 'status' => 301, 'from' => $path],
    ], $data['redirect']);

    // A language prefix in the requested path is kept in "from"; the target
    // follows the language of the router request.
    $data = $this->translate('/fr' . $path);
    $this->assertSame([
      ['to' => '/en' . $alias, 'status' => 301, 'from' => '/fr' . $path],
    ], $data['redirect']);

    // No redirect when the node has no translation in the request language.
    $data = $this->translate($path, 'fr');
    $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data));
    $this->assertNodeOutput($node, $data, '', 'fr');
  }

  /**
   * A node without alias: /node/N, no redirect.
   *
   * @covers ::translate
   */
  public function testNodeWithoutAlias(): void {
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Path translator ' . $this->randomMachineName(8),
      'status' => 1,
      'moderation_state' => 'published',
      'langcode' => 'en',
      'path' => ['pathauto' => PathautoState::SKIP],
    ]);
    $data = $this->translate('/node/' . $node->id());
    $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data));
    $this->assertNodeOutput($node, $data);
  }

  /**
   * Translated node: language prefixes in the path and on the endpoint.
   *
   * @covers ::translate
   */
  public function testOtherLanguage(): void {
    $node = $this->createPage(['title' => 'PT english ' . $this->randomMachineName(6)]);
    $en_alias = $node->get('path')->alias;
    $fr_alias = '/pt-fr-' . strtolower($this->randomMachineName(10));
    $fr_title = 'PT francais ' . $this->randomMachineName(6);
    $translation = $node->addTranslation('fr', [
      'title' => $fr_title,
      'status' => 1,
      'moderation_state' => 'published',
      'path' => ['alias' => $fr_alias, 'pathauto' => PathautoState::SKIP],
    ]);
    $translation->save();

    // French request, French prefixed alias.
    $data = $this->translate('/fr' . $fr_alias, 'fr');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data));
    $this->assertNodeOutput($node, $data, '', 'fr', $fr_title);
    $this->assertSame(200, $data['status']);

    // French request, French alias without prefix.
    $data = $this->translate($fr_alias, 'fr');
    $this->assertNodeOutput($node, $data, '', 'fr', $fr_title);

    // French request for /node/N: redirect to the French alias.
    $from = '/fr/node/' . $node->id();
    $data = $this->translate($from, 'fr');
    $this->assertSame([
      ['to' => '/fr' . $fr_alias, 'status' => 301, 'from' => $from],
    ], $data['redirect']);

    // English request, English alias.
    $data = $this->translate('/en' . $en_alias);
    $this->assertNodeOutput($node, $data, '', 'en', $node->getTranslation('en')->label());
  }

  /**
   * Unknown path: error page, status 404 in the body, HTTP 200.
   *
   * @covers ::translate
   */
  public function testUnknownPath(): void {
    $path = '/pt-unknown-' . strtolower($this->randomMachineName(10));
    $data = $this->translate($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame('application/json', $this->getSession()->getResponseHeader('Content-Type'));
    $this->assertErrorPage($data, 404, "No route found for $path");
  }

  /**
   * A Drupal route without entity: error page, 404 in the body.
   *
   * @covers ::translate
   */
  public function testRouteWithoutEntity(): void {
    $data = $this->translate('/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertErrorPage($data, 404, 'A route has been found for /admin/content , but it has no entity information!');
  }

  /**
   * Missing path parameter: HTTP 404.
   *
   * @covers ::translate
   */
  public function testMissingPath(): void {
    $this->drupalGet('/api/router');
    $this->assertSession()->statusCodeEquals(404);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertSame(['message' => 'Unable to translate empty path. Please send a ?path query string parameter with your request.'], $data);
  }

  /**
   * Unpublished node: error page for anonymous, the node in admin preview.
   *
   * @covers ::translate
   */
  public function testUnpublishedNode(): void {
    $node = $this->createPage(['status' => 0, 'moderation_state' => 'draft']);
    $alias = $node->get('path')->alias;

    $data = $this->translate($alias);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertErrorPage($data, 404, "A route has been found for $alias , but it has no entity information!");

    // Preview is ignored for users who cannot edit the node.
    $data = $this->translate($alias, 'en', ['preview' => 1]);
    $this->assertSame(404, $data['status']);

    // Preview as a user who can edit it: the node itself. Called in-process
    // (a browser login redirects to the decoupled frontend).
    $admin = $this->createUser([], NULL, TRUE);
    $data = $this->translateAs($admin, $alias, ['preview' => 1]);
    $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data));
    $this->assertNodeOutput($node, $data);
    $this->assertSame(200, $data['status']);

    // Without preview, even an administrator gets the error page.
    $data = $this->translateAs($admin, $alias);
    $this->assertSame(404, $data['status']);
    $this->assertSame(['_route' => 'error_page'], $data['system']);
  }

  /**
   * Redirect module entity wins over route matching.
   *
   * @covers ::translate
   */
  public function testRedirectEntity(): void {
    $node = $this->createPage();
    $alias = $node->get('path')->alias;
    $source = 'pt-old-' . strtolower($this->randomMachineName(10));
    $redirect = Redirect::create([
      'redirect_source' => ['path' => $source],
      'redirect_redirect' => ['uri' => 'internal:/node/' . $node->id()],
      'status_code' => 301,
      'language' => 'und',
    ]);
    $redirect->save();
    $this->markEntityForCleanup($redirect);

    $data = $this->translate('/' . $source);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame('application/json', $this->getSession()->getResponseHeader('Content-Type'));
    // The status codes come from the redirect entity as strings (other
    // outputs use integers).
    $this->assertSame([
      'status' => '301',
      'redirect' => [
        ['from' => '/' . $source, 'to' => '/en' . $alias, 'status' => '301'],
      ],
    ], $data);

    // Status code of the redirect entity is reported as is.
    $redirect->setStatusCode(302);
    $redirect->save();
    $data = $this->translate('/' . $source);
    $this->assertSame('302', $data['status']);
    $this->assertSame('302', $data['redirect'][0]['status']);

    // A redirect to an external URL.
    $redirect->set('redirect_redirect', ['uri' => 'https://example.com/target']);
    $redirect->save();
    $data = $this->translate('/' . $source);
    $this->assertSame('https://example.com/target', $data['redirect'][0]['to']);
  }

  /**
   * Account login page, resolved by its alias.
   *
   * @covers ::translate
   */
  public function testAccountLogin(): void {
    $route = Route::load('account_login');
    $this->assertNotNull($route);
    $node = $this->systemRouteNode('account_login');
    $data = $this->translate($route->getAlias());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(200, $data['status']);
    $this->assertSame($node->uuid(), $data['entity']['uuid']);
    $this->assertArrayNotHasKey('redirect', $data);
    $this->assertArrayNotHasKey('system', $data);

    // An extra segment is not matched.
    $path = $route->getAlias() . '/pt-extra';
    $this->assertErrorPage($this->translate($path), 404, "No route found for $path");
  }

  /**
   * A system route with a parameter: the "system" key.
   *
   * @covers ::translate
   */
  public function testSystemRouteWithParameter(): void {
    $node = $this->createPage();
    $base = '/pt-sys-' . strtolower($this->randomMachineName(10));
    $id = 'pt_test_' . strtolower($this->randomMachineName(8));
    $route = Route::create([
      'id' => $id,
      'label' => 'Path translator test route',
      'path' => '/node/' . $node->id(),
      'alias' => $base . '/{param1}',
    ]);
    $route->save();
    $this->markEntityForCleanup($route);

    $data = $this->translate($base . '/stuff');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(['entity', 'jsonapi', 'status', 'system'], array_keys($data));
    $this->assertNodeOutput($node, $data);
    $this->assertSame(200, $data['status']);
    $this->assertSame([
      '_query' => ['param1' => 'stuff'],
      '_route' => $id,
      'path' => '/node/' . $node->id(),
      'alias' => $base . '/{param1}',
      'request_path' => $base . '/stuff',
    ], $data['system']);
  }

  /**
   * Home page: "/" and a bare language prefix resolve to the front page.
   *
   * @covers ::translate
   */
  public function testHome(): void {
    $front = \Drupal::config('system.site')->get('page.front');
    $this->assertMatchesRegularExpression('#^/node/\d+$#', $front);
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(substr($front, strlen('/node/')));
    foreach (['/', '/en', '/fr'] as $path) {
      $data = $this->translate($path);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSame(['entity', 'jsonapi', 'status'], array_keys($data), $path);
      $this->assertNodeOutput($node, $data);
      $this->assertSame(200, $data['status']);
    }
  }

}
