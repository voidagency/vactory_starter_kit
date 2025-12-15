<?php

namespace Drupal\Tests\vactory_decoupled_router\Functional;

use Drupal\Core\Url;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\vactory_decoupled_router\Entity\Route;

/**
 * Functional tests for PathTranslator controller.
 *
 * @group vactory_decoupled_router
 */
class PathTranslatorTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected array $modulesToInstall = [
    'vactory_decoupled_router_default_pages',
    'vactory_decoupled_router',
  ];

  /**
   * Unsupported entities (not handled by helpers).
   *
   * @var array
   */
  private $createdEntities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in an admin user using DTT helper.
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);
  }

  /**
   * Test case 1: Node with alias - normal case.
   *
   * Create a page with a known alias, give the alias to the controller,
   * fetch and verify the result.
   */
  public function testNodeWithAlias() {
    // Create a published node using DTT helper.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Test Page with Alias',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    // Create an alias for the node.
    $langcode = $node->language()->getId();
    $alias = '/test-page-alias';
    $path_alias = PathAlias::create([
      'path' => '/node/' . $node->id(),
      'alias' => $alias,
      'langcode' => $langcode,
    ]);
    $path_alias->save();

    // Make request to the controller.
    $url = Url::fromRoute('vactory_decoupled_router.path_translation', [], [
      'query' => ['path' => $alias],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode JSON response.
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Verify response structure.
    $this->assertArrayHasKey('status', $response, 'The response array should contain a "status" key.');
    $this->assertEquals(200, $response['status'], 'The response status should be 200.');

    // Verify entity information.
    $this->assertArrayHasKey('entity', $response, 'The response array should contain an "entity" key.');
    $this->assertArrayHasKey('type', $response['entity'], 'The entity array should contain a "type" key.');
    $this->assertArrayHasKey('bundle', $response['entity'], 'The entity array should contain a "bundle" key.');
    $this->assertArrayHasKey('label', $response['entity'], 'The entity array should contain a "label" key.');
    $this->assertArrayHasKey('uuid', $response['entity'], 'The entity array should contain a "uuid" key.');
    $this->assertArrayHasKey('id', $response['entity'], 'The entity array should contain an "id" key.');

    $this->assertEquals('node', $response['entity']['type'], 'The entity type should be "node".');
    $this->assertEquals('vactory_page', $response['entity']['bundle'], 'The entity bundle should be "vactory_page".');
    $this->assertEquals('Test Page with Alias', $response['entity']['label'], 'The entity label should match the node title.');
    $this->assertEquals($node->uuid(), $response['entity']['uuid'], 'The entity UUID should match the node UUID.');
    $this->assertEquals($node->id(), $response['entity']['id'], 'The entity ID should match the node ID.');

    // Verify JSON:API information.
    $this->assertArrayHasKey('jsonapi', $response, 'The response array should contain a "jsonapi" key.');
    $this->assertArrayHasKey('individual', $response['jsonapi'], 'The jsonapi array should contain an "individual" key.');
    $this->assertStringContainsString("/node/vactory_page/{$node->uuid()}", $response['jsonapi']['individual'], 'The JSON:API individual URL should contain the node UUID.');
  }

  /**
   * Test case 2: Node with alias added to vactory_route.
   *
   * Create a page with a known alias, add the node in vactory_route,
   * give the alias to the controller, fetch and verify the result.
   */
  public function testNodeWithVactoryRoute() {
    // Create a published node using DTT helper.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Test Page with Vactory Route',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    // Create an alias for the node.
    $langcode = $node->language()->getId();
    $alias = '/test-vactory-route-alias';
    $requested_alias = "$alias/stuff";
    $path_alias = PathAlias::create([
      'path' => '/node/' . $node->id(),
      'alias' => $alias,
      'langcode' => $langcode,
    ]);
    $path_alias->save();

    // Create a vactory_route entry for this node.
    $vactory_route = Route::create([
      'id' => 'test_vactory_route',
      'label' => 'Test Vactory Route',
      'path' => '/node/' . $node->id(),
      'alias' => "$alias/{param1}",
    ]);
    $vactory_route->save();
    $this->createdEntities[] = $vactory_route;

    // Make request to the controller.
    $url = Url::fromRoute('vactory_decoupled_router.path_translation', [], [
      'query' => ['path' => $requested_alias],
    ]);
    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode JSON response.
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Verify response structure.
    $this->assertArrayHasKey('status', $response, 'The response array should contain a "status" key.');
    $this->assertEquals(200, $response['status'], 'The response status should be 200.');

    // Verify entity information.
    $this->assertArrayHasKey('entity', $response, 'The response array should contain an "entity" key.');
    $this->assertArrayHasKey('type', $response['entity'], 'The entity array should contain a "type" key.');
    $this->assertArrayHasKey('bundle', $response['entity'], 'The entity array should contain a "bundle" key.');
    $this->assertArrayHasKey('label', $response['entity'], 'The entity array should contain a "label" key.');
    $this->assertArrayHasKey('uuid', $response['entity'], 'The entity array should contain a "uuid" key.');
    $this->assertArrayHasKey('id', $response['entity'], 'The entity array should contain an "id" key.');

    $this->assertEquals('node', $response['entity']['type'], 'The entity type should be "node".');
    $this->assertEquals('vactory_page', $response['entity']['bundle'], 'The entity bundle should be "vactory_page".');
    $this->assertEquals('Test Page with Vactory Route', $response['entity']['label'], 'The entity label should match the node title.');
    $this->assertEquals($node->uuid(), $response['entity']['uuid'], 'The entity UUID should match the node UUID.');
    $this->assertEquals($node->id(), $response['entity']['id'], 'The entity ID should match the node ID.');

    // Verify JSON:API information.
    $this->assertArrayHasKey('jsonapi', $response, 'The response array should contain a "jsonapi" key.');
    $this->assertArrayHasKey('individual', $response['jsonapi'], 'The jsonapi array should contain an "individual" key.');
    $this->assertStringContainsString("/node/vactory_page/{$node->uuid()}", $response['jsonapi']['individual'], 'The JSON:API individual URL should contain the node UUID.');

    // Verify system route information is present.
    $this->assertArrayHasKey('system', $response, 'The response array should contain a "system" key when using vactory_route.');
    $this->assertArrayHasKey('_route', $response['system'], 'The system array should contain a "_route" key.');
    $this->assertArrayHasKey('path', $response['system'], 'The system array should contain a "path" key.');

    $this->assertEquals('test_vactory_route', $response['system']['_route'], 'The system route should match the vactory_route ID.');
    $this->assertEquals('/node/' . $node->id(), $response['system']['path'], 'The system path should match the node path.');
  }

  /**
   * Test case 3: Non-existent path returns 404.
   */
  public function testNonExistentPath() {
    $non_existent_path = '/non-existent-page';

    // Make request to the controller.
    $url = Url::fromRoute('vactory_decoupled_router.path_translation', [], [
      'query' => ['path' => $non_existent_path],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode JSON response.
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Verify 404 status in response.
    $this->assertArrayHasKey('status', $response, 'The response array should contain a "status" key.');
    $this->assertEquals(404, $response['status'], 'The response status should be 404 for non-existent paths.');

    // Verify error_page route is used.
    $this->assertArrayHasKey('system', $response, 'The response array should contain a "system" key for error pages.');
    $this->assertArrayHasKey('_route', $response['system'], 'The system array should contain a "_route" key.');
    $this->assertEquals('error_page', $response['system']['_route'], 'The system route should be "error_page" for 404 errors.');
  }

  /**
   * Test case 4: Unpublished node returns 404.
   */
  public function testUnpublishedNode() {
    // Create an unpublished node using DTT helper.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Unpublished Page',
      'status' => 0,
    ]);

    // Create an alias for the node.
    $langcode = $node->language()->getId();
    $alias = '/unpublished-page';
    $path_alias = PathAlias::create([
      'path' => '/node/' . $node->id(),
      'alias' => $alias,
      'langcode' => $langcode,
    ]);
    $path_alias->save();

    // Make request to the controller.
    $url = Url::fromRoute('vactory_decoupled_router.path_translation', [], [
      'query' => ['path' => $alias],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode JSON response.
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Verify 404 status (unpublished nodes should not be found).
    $this->assertArrayHasKey('status', $response, 'The response array should contain a "status" key.');
    $this->assertEquals(404, $response['status'], 'The response status should be 404 for unpublished nodes.');

    // Verify error_page route is used.
    $this->assertArrayHasKey('system', $response, 'The response array should contain a "system" key for error pages.');
    $this->assertArrayHasKey('_route', $response['system'], 'The system array should contain a "_route" key.');
    $this->assertEquals('error_page', $response['system']['_route'], 'The system route should be "error_page" for unpublished nodes.');
  }

  /**
   * Test case 5: Node path pattern redirects to alias.
   *
   * When accessing /node/123, if an alias exists, it should redirect.
   */
  public function testNodePathPatternRedirect() {
    // Create a published node using DTT helper.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Test Redirect Page',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    // Create an alias for the node.
    $langcode = $node->language()->getId();
    $alias = '/test-redirect-alias';
    $path_alias = PathAlias::create([
      'path' => '/node/' . $node->id(),
      'alias' => $alias,
      'langcode' => $langcode,
    ]);
    $path_alias->save();

    // Make request to the controller with node path pattern.
    $node_path = '/node/' . $node->id();
    $url = Url::fromRoute('vactory_decoupled_router.path_translation', [], [
      'query' => ['path' => $node_path],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode JSON response.
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Verify redirect information is present.
    $this->assertArrayHasKey('redirect', $response, 'The response array should contain a "redirect" key when node path pattern is used.');
    $this->assertIsArray($response['redirect'], 'The redirect value should be an array.');
    $this->assertCount(1, $response['redirect'], 'The redirect array should contain exactly one redirect entry.');

    $redirect = reset($response['redirect']);

    $this->assertArrayHasKey('to', $redirect, 'The redirect array should contain a "to" key.');
    $this->assertStringContainsString($alias, $redirect['to'], 'The redirect "to" value should contain the node alias.');

    $this->assertArrayHasKey('status', $redirect, 'The redirect array should contain a "status" key.');
    $this->assertEquals(301, $redirect['status'], 'The redirect status should be 301 (Moved Permanently).');

    $this->assertArrayHasKey('from', $redirect, 'The redirect array should contain a "from" key.');
    $this->assertEquals($node_path, $redirect['from'], 'The redirect "from" value should match the node path pattern.');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (!empty($this->createdEntities)) {
      foreach ($this->createdEntities as $entity) {
        $entity->delete();
      }
    }
    parent::tearDown();
  }

}
