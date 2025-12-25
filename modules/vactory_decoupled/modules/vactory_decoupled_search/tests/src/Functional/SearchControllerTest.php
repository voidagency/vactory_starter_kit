<?php

namespace Drupal\Tests\vactory_decoupled_search\Functional;

use Drupal\Core\Url;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Functional tests for SearchController.
 *
 * @group vactory_decoupled_search
 */
class SearchControllerTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected array $modulesToInstall = [
    'vactory_decoupled_search',
    'search_api',
  ];

  /**
   * The search index entity.
   *
   * @var \Drupal\search_api\Entity\Index|null
   */
  protected $searchIndex;

  /**
   * Created nodes for testing.
   *
   * @var array
   */
  protected $testNodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get entity type manager from container.
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create and log in an admin user using DTT helper.
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Configure the search index.
    $this->configureSearchIndex();

    // Create test nodes.
    $this->createTestNodes();

    // Index the nodes.
    $this->indexNodes();
  }

  /**
   * Configure the search index to index vactory_page content type.
   */
  protected function configureSearchIndex() {
    $index_storage = $this->entityTypeManager->getStorage('search_api_index');
    $this->searchIndex = $index_storage->load('default_content_index');

    if (!$this->searchIndex) {
      $this->markTestSkipped('The default_content_index does not exist. Please ensure Search API is properly configured.');
    }

    // Get datasources to check if entity:node is available.
    $datasources = $this->searchIndex->getDatasources();
    if (!isset($datasources['entity:node'])) {
      $this->markTestSkipped('The default_content_index does not have entity:node datasource configured.');
    }

    // Get current datasource settings.
    $datasource_settings = $this->searchIndex->get('datasource_settings');

    // Configure the index to include vactory_page.
    if (isset($datasource_settings['entity:node'])) {
      $node_settings = $datasource_settings['entity:node'];
      $node_settings['bundles']['default'] = FALSE;
      $node_settings['bundles']['selected'] = array_unique(array_merge(
        $node_settings['bundles']['selected'] ?? [],
        ['vactory_page']
      ));
      $datasource_settings['entity:node'] = $node_settings;

      $this->modifyConfigValue('search_api.index.default_content_index', 'datasource_settings', $datasource_settings);

      // Reindex to apply changes.
      $this->searchIndex->clear();
    }
  }

  /**
   * Create test nodes with known titles.
   */
  protected function createTestNodes() {
    // Create nodes with specific titles for testing.
    $this->testNodes['drupal'] = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Drupal Content Management System',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $this->testNodes['vactory'] = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Vactory Starter Kit Framework',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $this->testNodes['search'] = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Search API Integration Test',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    $this->testNodes['drupal_search'] = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Drupal Search Functionality',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    // Create an unpublished node (should not appear in search).
    $this->testNodes['unpublished'] = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Unpublished Drupal Page',
      'status' => 0,
    ]);
  }

  /**
   * Index the created nodes.
   */
  protected function indexNodes() {
    if ($this->searchIndex) {
      $this->searchIndex->indexItems();
    }
  }

  /**
   * Search with results - basic search.
   *
   * Search for a keyword that matches created nodes and verify results.
   */
  public function testSearchWithResults() {
    $search_term = 'Drupal';
    $url = Url::fromRoute('vactory_decoupled_search.search_api_page', [], [
      'query' => ['q' => $search_term],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode JSON response.
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Verify response structure.
    $this->assertArrayHasKey('status', $response, 'The response array should contain a "status" key.');
    $this->assertEquals(200, $response['status'], 'The response status should be 200 for successful search.');

    $this->assertArrayHasKey('resources', $response, 'The response array should contain a "resources" key.');
    $this->assertArrayHasKey('count', $response, 'The response array should contain a "count" key.');

    $this->assertIsArray($response['resources'], 'The resources value should be an array.');
    $this->assertGreaterThan(0, $response['count'], 'The search should return at least one result for "Drupal".');

    // Verify that results contain expected nodes.
    $titles = array_column($response['resources'], 'title');
    $this->assertContains('Drupal Content Management System', $titles, 'The search results should contain the "Drupal Content Management System" node.');
    $this->assertContains('Drupal Search Functionality', $titles, 'The search results should contain the "Drupal Search Functionality" node.');

    // Verify structure of each result item.
    foreach ($response['resources'] as $resource) {
      $this->assertArrayHasKey('url', $resource, 'Each resource should contain a "url" key.');
      $this->assertArrayHasKey('title', $resource, 'Each resource should contain a "title" key.');
      $this->assertArrayHasKey('type', $resource, 'Each resource should contain a "type" key.');
      $this->assertArrayHasKey('excerpt', $resource, 'Each resource should contain an "excerpt" key.');

      $this->assertEquals('vactory_page', $resource['type'], 'The resource type should be "vactory_page".');
      $this->assertNotEmpty($resource['title'], 'The resource title should not be empty.');
    }
  }

  /**
   * Search with no results.
   *
   * Search for a keyword that doesn't match any nodes.
   */
  public function testSearchWithNoResults() {
    $search_term = 'NonexistentKeyword12345';
    $url = Url::fromRoute('vactory_decoupled_search.search_api_page', [], [
      'query' => ['q' => $search_term],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode JSON response.
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Verify response structure.
    $this->assertArrayHasKey('status', $response, 'The response array should contain a "status" key.');
    $this->assertEquals(200, $response['status'], 'The response status should be 200 even when no results are found.');

    $this->assertArrayHasKey('resources', $response, 'The response array should contain a "resources" key.');
    $this->assertArrayHasKey('count', $response, 'The response array should contain a "count" key.');

    $this->assertIsArray($response['resources'], 'The resources value should be an array.');
    $this->assertEquals(0, $response['count'], 'The search count should be 0 when no results are found.');
    $this->assertEmpty($response['resources'], 'The resources array should be empty when no results are found.');
  }

  /**
   * Search with empty query parameter.
   *
   * Search without providing a query parameter should return 400.
   */
  public function testSearchWithEmptyQuery() {
    $url = Url::fromRoute('vactory_decoupled_search.search_api_page');

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode JSON response.
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Verify error response.
    $this->assertArrayHasKey('status', $response, 'The response array should contain a "status" key.');
    $this->assertEquals(400, $response['status'], 'The response status should be 400 when query parameter is empty.');

    $this->assertArrayHasKey('resources', $response, 'The response array should contain a "resources" key.');
    $this->assertArrayHasKey('count', $response, 'The response array should contain a "count" key.');

    $this->assertIsArray($response['resources'], 'The resources value should be an array.');
    $this->assertEquals(0, $response['count'], 'The search count should be 0 when query is empty.');
    $this->assertEmpty($response['resources'], 'The resources array should be empty when query is empty.');
  }

  /**
   * Search with pagination.
   *
   * Test that pagination works correctly.
   */
  public function testSearchWithPagination() {
    $search_term = 'Drupal';
    $limit = 1;

    // First page.
    $url = Url::fromRoute('vactory_decoupled_search.search_api_page', [], [
      'query' => [
        'q' => $search_term,
        'limit' => $limit,
        'pager' => 1,
      ],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    $this->assertSession()->statusCodeEquals(200);
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    $this->assertArrayHasKey('resources', $response, 'The response array should contain a "resources" key.');
    $this->assertArrayHasKey('count', $response, 'The response array should contain a "count" key.');

    $first_page_count = count($response['resources']);
    $this->assertLessThanOrEqual($limit, $first_page_count, 'The first page should have at most the specified limit of results.');

    // Second page.
    $url = Url::fromRoute('vactory_decoupled_search.search_api_page', [], [
      'query' => [
        'q' => $search_term,
        'limit' => $limit,
        'pager' => 2,
      ],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    $this->assertSession()->statusCodeEquals(200);
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    $this->assertArrayHasKey('resources', $response, 'The response array should contain a "resources" key.');
    $second_page_count = count($response['resources']);

    // Verify that pages return different results (if there are enough results).
    if ($response['count'] > $limit) {
      $this->assertGreaterThan(0, $second_page_count, 'The second page should have results if total count exceeds limit.');
    }
  }

  /**
   * Unpublished nodes should not appear in search.
   *
   * Verify that unpublished nodes are not returned in search results.
   */
  public function testUnpublishedNodesNotInSearch() {
    $search_term = 'Unpublished';
    $url = Url::fromRoute('vactory_decoupled_search.search_api_page', [], [
      'query' => ['q' => $search_term],
    ]);

    $full_url = $this->baseUrl . $url->toString();
    $this->drupalGet($full_url);

    $this->assertSession()->statusCodeEquals(200);
    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    $this->assertArrayHasKey('resources', $response, 'The response array should contain a "resources" key.');
    $this->assertArrayHasKey('count', $response, 'The response array should contain a "count" key.');

    // Unpublished nodes should not appear in search results.
    $titles = array_column($response['resources'], 'title');
    $this->assertNotContains('Unpublished Drupal Page', $titles, 'Unpublished nodes should not appear in search results.');
  }

}
