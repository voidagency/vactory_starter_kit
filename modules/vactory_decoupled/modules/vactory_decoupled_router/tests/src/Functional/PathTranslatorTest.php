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
    $this->assertArrayHasKey('status', $response);
    $this->assertEquals(200, $response['status']);

    // Verify entity information.
    $this->assertArrayHasKey('entity', $response);
    $this->assertArrayHasKey('type', $response['entity']);
    $this->assertArrayHasKey('bundle', $response['entity']);
    $this->assertArrayHasKey('label', $response['entity']);
    $this->assertArrayHasKey('uuid', $response['entity']);
    $this->assertArrayHasKey('id', $response['entity']);

    $this->assertEquals('node', $response['entity']['type']);
    $this->assertEquals('vactory_page', $response['entity']['bundle']);
    $this->assertEquals('Test Page with Alias', $response['entity']['label']);
    $this->assertEquals($node->uuid(), $response['entity']['uuid']);
    $this->assertEquals($node->id(), $response['entity']['id']);

    // Verify JSON:API information.
    $this->assertArrayHasKey('jsonapi', $response);
    $this->assertArrayHasKey('individual', $response['jsonapi']);
    $this->assertStringContainsString("/node/vactory_page/{$node->uuid()}", $response['jsonapi']['individual']);
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
    $this->assertArrayHasKey('status', $response);
    $this->assertEquals(200, $response['status']);

    // Verify entity information.
    $this->assertArrayHasKey('entity', $response);
    $this->assertArrayHasKey('type', $response['entity']);
    $this->assertArrayHasKey('bundle', $response['entity']);
    $this->assertArrayHasKey('label', $response['entity']);
    $this->assertArrayHasKey('uuid', $response['entity']);
    $this->assertArrayHasKey('id', $response['entity']);

    $this->assertEquals('node', $response['entity']['type']);
    $this->assertEquals('vactory_page', $response['entity']['bundle']);
    $this->assertEquals('Test Page with Vactory Route', $response['entity']['label']);
    $this->assertEquals($node->uuid(), $response['entity']['uuid']);
    $this->assertEquals($node->id(), $response['entity']['id']);

    // Verify JSON:API information.
    $this->assertArrayHasKey('jsonapi', $response);
    $this->assertArrayHasKey('individual', $response['jsonapi']);
    $this->assertStringContainsString("/node/vactory_page/{$node->uuid()}", $response['jsonapi']['individual']);

    // Verify system route information is present.
    $this->assertArrayHasKey('system', $response);
    $this->assertArrayHasKey('_route', $response['system']);
    $this->assertArrayHasKey('path', $response['system']);

    $this->assertEquals('test_vactory_route', $response['system']['_route']);
    $this->assertEquals('/node/' . $node->id(), $response['system']['path']);
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
    $this->assertArrayHasKey('status', $response);
    $this->assertEquals(404, $response['status']);

    // Verify error_page route is used.
    $this->assertArrayHasKey('system', $response);
    $this->assertArrayHasKey('_route', $response['system']);
    $this->assertEquals('error_page', $response['system']['_route']);
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
    $this->assertArrayHasKey('status', $response);
    $this->assertEquals(404, $response['status']);

    // Verify error_page route is used.
    $this->assertArrayHasKey('system', $response);
    $this->assertArrayHasKey('_route', $response['system']);
    $this->assertEquals('error_page', $response['system']['_route']);
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
    $this->assertArrayHasKey('redirect', $response);
    $this->assertIsArray($response['redirect']);
    $this->assertCount(1, $response['redirect']);

    $redirect = reset($response['redirect']);

    $this->assertArrayHasKey('to', $redirect);
    $this->assertStringContainsString($alias, $redirect['to']);

    $this->assertArrayHasKey('status', $redirect);
    $this->assertEquals(301, $redirect['status']);

    $this->assertArrayHasKey('from', $redirect);
    $this->assertEquals($node_path, $redirect['from']);
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
