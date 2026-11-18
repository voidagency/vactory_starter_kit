<?php

namespace Drupal\Tests\vactory_jsonapi_extras\ExistingSite;

use Drupal\Core\Url;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\search_api\Entity\Index;

/**
 * Our use of drupal/jsonapi_search_api.
 *
 * Exposed APIs of type Search API build routes on the module's IndexResource
 * class (vactory_jsonapi_extras/src/Routing/Routes.php). jsonapi_search_api
 * is pinned (1.0.0-rc3) after several regressions: run this test on a
 * candidate version before upgrading it.
 *
 * @group vactory_jsonapi_extras
 * @group dependencies
 */
class ExposedSearchIndexApiTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = ['jsonapi_search_api'];

  /**
   * Exposed API created by the test.
   *
   * @var \Drupal\vactory_jsonapi_extras\ExposedApisInterface|null
   */
  protected $api;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Remove the API and its route before the module is uninstalled.
    if ($this->api) {
      $this->api->delete();
      \Drupal::service('router.builder')->rebuild();
    }
    parent::tearDown();
  }

  /**
   * An exposed Search API index answers with the indexed content.
   */
  public function testExposedIndexApi(): void {
    $index = Index::load('default_content_index');
    $this->assertNotNull($index);
    $this->assertTrue($index->status() && $index->isServerEnabled(), 'The default content index is enabled.');

    // Letters only: the fulltext tokenizer splits words on digits.
    $marker = 'searchapi' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 10);
    $nodes = [];
    $item_ids = [];
    foreach ([1, 2] as $i) {
      $node = $this->createNode([
        'type' => 'vactory_page',
        'title' => "$marker $i",
        'status' => 1,
        // Content moderation would save a draft (excluded from the index).
        'moderation_state' => 'published',
      ]);
      $nodes[] = $node;
      $item_ids[] = 'entity:node/' . $node->id() . ':' . $node->language()->getId();
    }
    $index->indexSpecificItems($index->loadItemsMultiple($item_ids));

    $this->api = \Drupal::entityTypeManager()->getStorage('exposed_apis')->create([
      'id' => 'test_search_' . strtolower($this->randomMachineName(6)),
      'label' => 'Test search API',
      'status' => TRUE,
      'is_search_api_resource' => TRUE,
      'search_api_resource' => 'default_content_index',
      // Search routes are prefixed with the JSON:API base path.
      'path' => '/test-search-' . strtolower($this->randomMachineName(6)),
      'packages' => [],
    ]);
    $this->api->save();
    \Drupal::service('router.builder')->rebuild();

    $route_name = 'exposed_api.search.' . $this->api->id();
    $route = \Drupal::service('router.route_provider')->getRouteByName($route_name);
    $this->assertSame('Drupal\jsonapi_search_api\Resource\IndexResource', $route->getDefault('_jsonapi_resource'));
    $this->assertContains('node--vactory_page', $route->getDefault('_jsonapi_resource_types'));

    // A fulltext search plus a field condition (applyFiltersToQuery()).
    $url = Url::fromRoute($route_name, [], [
      'absolute' => TRUE,
      'query' => ['filter' => ['fulltext' => $marker, 'type' => 'vactory_page']],
    ])->toString();
    $response = \Drupal::httpClient()->get($url, [
      'headers' => ['Accept' => 'application/vnd.api+json'],
      'http_errors' => FALSE,
    ]);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $body = json_decode((string) $response->getBody(), TRUE);
    $titles = array_column(array_column($body['data'] ?? [], 'attributes'), 'title');
    foreach ($nodes as $node) {
      $this->assertContains($node->getTitle(), $titles);
    }
    $this->assertEquals(2, $body['meta']['count'] ?? NULL);
  }

}
