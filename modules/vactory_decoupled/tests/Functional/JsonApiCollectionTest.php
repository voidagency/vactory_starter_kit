<?php

namespace Drupal\vactory_decoupled\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Validate JSON API Collection paragraphs in decoupled context.
 *
 * @group vactory_decoupled
 */
class JsonApiCollectionTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected array $modulesToInstall = ['vactory_news'];

  /**
   * Default collection setting (news case).
   */
  const DEFAULT_COLLECTION_SETTING = [
    'name' => 'News List',
    'multiple' => FALSE,
    'category' => 'Test',
    'enabled' => TRUE,
    'fields' => [
      'collection' => [
        'type' => 'json_api_collection',
        'label' => 'JSON:API',
        'options' => [
          '#required' => TRUE,
          '#default_value' => [
            'resource' => 'node--vactory_news',
            'filters' => [
              'fields[node--vactory_news]=drupal_internal__nid,title',
              'page[offset]=0',
              'page[limit]=9',
              'sort[sort-vactory-date][path]=field_vactory_date',
              'sort[sort-vactory-date][direction]=DESC',
              'filter[status][value]=1',
            ],
            'vocabularies' => [
              'vactory_news_theme' => 'vactory_news_theme',
            ],
          ],
        ],
      ],
    ],
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {

    parent::setUp();

    // Create and log in an admin user using DTT helper.
    $adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($adminUser);

    \Drupal::service("router.builder")->rebuild();

    // Create news nodes.
    // we should have at least one node.
    $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test 1',
      'status' => 1,
    ]);
    $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test 2',
      'status' => 1,
    ]);

  }

  /**
   * Test JSON API Collection paragraph in a vactory_page.
   */
  public function testJsonApiCollectionParagraph(): void {
    // Prepare the DF.
    $df_name = 'test-news-listing';
    $widget_id = $this->createVolatileDf(self::DEFAULT_COLLECTION_SETTING, $df_name);

    // Create a JSON API Collection paragraph.
    $widget_data = [
      [
        'collection' => self::DEFAULT_COLLECTION_SETTING['fields']['collection']['options']['#default_value'],
      ],
    ];

    $paragraph = $this->createParagraph([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);

    // Create a vactory_page with the paragraph.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Test JSON API Collection Page',
      'status' => 1,
      'moderation_state' => 'published',
      'field_vactory_paragraphs' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);

    $langcode = $node->language()->getId();
    $query_params = ['include' => 'field_vactory_paragraphs'];
    $json = $this->fetchNodeJsonApi($node, $langcode, 200, $query_params);

    $data = $json['included'][0]['attributes']['field_vactory_component']['widget_data'];
    // Decode the widget_data JSON.
    $widget_data_decoded = json_decode($data, TRUE);

    // Get the JSON API collection response.
    $collection_response = $widget_data_decoded['components'][0]['collection']['data'] ?? [];

    // Validate JSON API collection response structure.
    $this->assertArrayHasKey('data', $collection_response, 'Collection response should have data key');
    $this->assertIsArray($collection_response['data'], 'Collection data should be an array');

    // Check if nodes are presents.
    $this->assertNotEmpty($collection_response['data'], 'Collection data should not be empty');

    // Verify we have vactory_news nodes.
    if (!empty($collection_response['data'])) {
      foreach ($collection_response['data'] as $item) {
        $this->assertEquals('node--vactory_news', $item['type'], 'Item should be of type node--vactory_news');
        $this->assertArrayHasKey('id', $item, 'Item should have an id');
        $this->assertArrayHasKey('attributes', $item, 'Item should have attributes');
      }
    }
  }

}
