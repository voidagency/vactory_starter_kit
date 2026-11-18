<?php

namespace Drupal\Tests\vactory_jsonapi_extras\ExistingSite;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\search_api\Entity\Index;

/**
 * Our use of drupal/jsonapi_search_api.
 *
 * "Exposed APIs" of type Search API build routes on the module's
 * IndexResource class (vactory_jsonapi_extras/src/Routing/Routes.php).
 * jsonapi_search_api is pinned (1.0.0-rc3) after several regressions: run
 * this test on a candidate version before upgrading it.
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
   * An exposed Search API index answers with the indexed content.
   */
  public function testExposedIndexApi(): void {
    // Letters only: the fulltext tokenizer splits words on digits.
    $marker = 'searchapi' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 10);
    $nodes = [];
    foreach ([1, 2] as $i) {
      $nodes[] = $this->createNode([
        'type' => 'vactory_page',
        'title' => "$marker $i",
        'status' => 1,
        // Content moderation would save a draft (excluded from the index).
        'moderation_state' => 'published',
      ]);
    }
    $index = Index::load('default_content_index');
    $this->assertNotNull($index);
    $index->indexItems();

    // Search routes are prefixed with the JSON:API base path.
    $path = '/test-search-' . strtolower($this->randomMachineName(6));
    $api = \Drupal::entityTypeManager()->getStorage('exposed_apis')->create([
      'id' => 'test_search_' . strtolower($this->randomMachineName(6)),
      'label' => 'Test search API',
      'status' => TRUE,
      'is_search_api_resource' => TRUE,
      'search_api_resource' => 'default_content_index',
      'path' => $path,
      'packages' => [],
    ]);
    $api->save();
    $this->markEntityForCleanup($api);
    \Drupal::service('router.builder')->rebuild();

    $route = \Drupal::service('router.route_provider')->getRouteByName('exposed_api.search.' . $api->id());
    $this->assertSame('Drupal\jsonapi_search_api\Resource\IndexResource', $route->getDefault('_jsonapi_resource'));

    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $url = $this->baseUrl . '/' . $langcode . $route->getPath() . '?filter[fulltext]=' . $marker;
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
    $this->assertArrayHasKey('count', $body['meta'] ?? []);
  }

}
