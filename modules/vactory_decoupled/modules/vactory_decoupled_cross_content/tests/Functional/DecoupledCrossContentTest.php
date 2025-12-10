<?php

namespace Drupal\vactory_decoupled_cross_content\Functional;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Validate decoupled nodes metatags.
 *
 * @group vactory_decoupled
 */
class DecoupledCrossContentTest extends VactoryExistingSiteBase {

  const COLLECTION_SETTING = [
    'name' => 'Cross content news test',
    'multiple' => FALSE,
    'enabled' => TRUE,
    'fields' => [
      'collection' => [
        'type' => 'json_api_cross_content',
        'label' => 'JSON:API',
        'options' => [
          '#required' => TRUE,
          '#default_value' => [
            'resource' => 'node--vactory_news',
            'filters' => [
              'fields[node--vactory_news]=drupal_internal__nid,title',
              'sort[sort-vactory-date][path]=field_vactory_date',
              'sort[sort-vactory-date][direction]=DESC',
              'filter[status][value]=1',
            ],
          ],
        ],
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = [
    'vactory_news',
    'vactory_decoupled',
    'vactory_decoupled_cross_content',
    'vactory_cross_content',
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
   * Test cross content by vocabulary.
   */
  public function testCrossContentByVocabulary() {
    // Enable cross content for news.
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.enabling', TRUE);
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.taxonomy_field', 'field_vactory_news_theme');
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.nombre_elements', '2');

    // Load vactory_news_theme vocab.
    $vocabulary = Vocabulary::load('vactory_news_theme');

    // Create term.
    $term = $this->createTerm($vocabulary);

    // Create nodes (news).
    $node1 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 1',
      'status' => 1,
      'field_vactory_news_theme' => $term->id(),
    ]);

    $node2 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 2',
      'status' => 1,
      'field_vactory_news_theme' => $term->id(),
    ]);

    $node3 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 3',
      'status' => 1,
      'field_vactory_news_theme' => $term->id(),
    ]);

    $node4 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 4',
      'status' => 1,
      'field_vactory_news_theme' => $term->id(),
    ]);

    // Prepare the DF.
    $df_name = 'test-cross-content';
    $widget_id = $this->createVolatileDf(self::COLLECTION_SETTING, $df_name);

    // Create a JSON API Collection paragraph.
    $widget_data = [
      [
        'collection' => self::COLLECTION_SETTING['fields']['collection']['options']['#default_value'],
      ],
    ];

    $block_content = BlockContent::create([
      'type' => 'vactory_block_component',
      'info' => 'Cross content widget test',
      'block_machine_name' => 'cross_content_widget_test',
      'field_dynamic_block_components' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);
    $block_content->save();
    $this->createdEntities[] = $block_content;

    $block_entity = Block::create([
      'id' => 'cross_content_widget_test',
      'theme' => 'vactory',
      'region' => 'footer',
      'plugin' => 'block_content:' . $block_content->uuid(),
      'settings' => [
        'id' => 'block_content:' . $block_content->uuid(),
        'label' => 'Cross content widget test',
        'label_display' => TRUE,
        'provider' => 'block_content',
        'status' => 1,
      ],
      'visibility' => [
        'entity_bundle:node' => [
          'id' => 'entity_bundle:node',
          'bundles' => ['vactory_news' => 'vactory_news'],
          'negate' => FALSE,
          'context_mapping' => [
            'entity' => '@node.node_route_context:node',
          ],
        ],
      ],
    ]);

    $block_entity->save();
    $this->createdEntities[] = $block_entity;

    // Test with json api.
    $langcode = $node1->language()->getId();
    $data = $this->fetchNodeJsonApi($node1, $langcode);

    $blocks = $data['data']['attributes']['internal_blocks'] ?? [];

    $this->assertIsArray($blocks, 'internal_blocks should be an array');
    $this->assertNotEmpty($blocks, 'internal_blocks should not be empty');

    // Find the created block.
    $target = NULL;

    foreach ($blocks as $block) {
      if (($block['id'] ?? NULL) === 'cross_content_widget_test') {
        $target = $block;
        break;
      }
    }

    $this->assertNotNull(
      $target,
      'A block with id "cross_content_widget_test" should exist.'
    );

    $this->assertArrayHasKey('content', $target, 'The block must contain a "content" key.');
    $this->assertIsArray($target['content'], '"content" key must be an array.');
    $this->assertArrayHasKey('widget_id', $target['content'], '"content" must contain "widget_id".');
    $this->assertEquals($widget_id, $target['content']['widget_id'], '"widget_id" value is incorrect.');
    $this->assertArrayHasKey('widget_data', $target['content'], '"content" must contain "widget_data".');
    $widgetDataJson = $target['content']['widget_data'];
    $this->assertIsString($widgetDataJson, '"widget_data" must be a JSON string.');
    $decoded = json_decode($widgetDataJson, TRUE);
    $this->assertNotNull($decoded, '"widget_data" must be valid JSON.');
    $this->assertIsArray($decoded, 'Decoded widget_data must be an array.');

    $this->assertArrayHasKey('components', $decoded, 'JSON API must contain "components".');
    $this->assertIsArray($decoded['components'], '"components" must be an array.');
    $this->assertNotEmpty($decoded['components'], '"components" cannot be empty.');
    $collection = $decoded['components'][0]['collection']['data'] ?? NULL;
    $this->assertNotNull($collection, 'JSON API collection data must exist.');
    $this->assertArrayHasKey('data', $collection, 'JSON API must contain data array.');
    $this->assertIsArray($collection['data'], 'JSON API data must be an array.');
    $this->assertCount(2, $collection['data'], 'JSON API must return exactly 2 vactory_news nodes.');
    $allowedNids = [
      (int) $node1->id(),
      (int) $node2->id(),
      (int) $node3->id(),
      (int) $node4->id(),
    ];

    $retrievedNids = [];
    foreach ($collection['data'] as $item) {
      $this->assertEquals('node--vactory_news', $item['type'], 'Each returned item must be of type node--vactory_news.');
      $this->assertArrayHasKey('attributes', $item, 'JSON API item must contain attributes.');
      $this->assertArrayHasKey('drupal_internal__nid', $item['attributes'], 'Node must contain drupal_internal__nid attribute.');
      $retrievedNids[] = (int) $item['attributes']['drupal_internal__nid'];
    }

    foreach ($retrievedNids as $nid) {
      $this->assertContains($nid, $allowedNids, "Node nid $nid is not in allowed list.");
    }
  }

  /**
   * Test cross content by term.
   */
  public function testCrossContentByTerm() {
    // Load vactory_news_theme vocab.
    $vocabulary = Vocabulary::load('vactory_news_theme');

    // Create terms.
    $term1 = $this->createTerm($vocabulary);
    $term2 = $this->createTerm($vocabulary);

    // Enable cross content for news.
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.enabling', TRUE);
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.taxonomy_field', 'none');
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.terms', [$term2->id()]);
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.nombre_elements', '2');

    // Create nodes (news).
    $node1 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 1',
      'status' => 1,
      'field_vactory_news_theme' => $term1->id(),
    ]);

    $node2 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 2',
      'status' => 1,
      'field_vactory_news_theme' => $term1->id(),
    ]);

    $node3 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 3',
      'status' => 1,
      'field_vactory_news_theme' => $term2->id(),
    ]);

    $node4 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 4',
      'status' => 1,
      'field_vactory_news_theme' => $term2->id(),
    ]);

    $node5 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 5',
      'status' => 1,
      'field_vactory_news_theme' => $term2->id(),
    ]);

    // Prepare the DF.
    $df_name = 'test-cross-content';
    $widget_id = $this->createVolatileDf(self::COLLECTION_SETTING, $df_name);

    // Create a JSON API Collection paragraph.
    $widget_data = [
      [
        'collection' => self::COLLECTION_SETTING['fields']['collection']['options']['#default_value'],
      ],
    ];

    $block_content = BlockContent::create([
      'type' => 'vactory_block_component',
      'info' => 'Cross content widget test',
      'block_machine_name' => 'cross_content_widget_test',
      'field_dynamic_block_components' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);
    $block_content->save();
    $this->createdEntities[] = $block_content;

    $block_entity = Block::create([
      'id' => 'cross_content_widget_test',
      'theme' => 'vactory',
      'region' => 'footer',
      'plugin' => 'block_content:' . $block_content->uuid(),
      'settings' => [
        'id' => 'block_content:' . $block_content->uuid(),
        'label' => 'Cross content widget test',
        'label_display' => TRUE,
        'provider' => 'block_content',
        'status' => 1,
      ],
      'visibility' => [
        'entity_bundle:node' => [
          'id' => 'entity_bundle:node',
          'bundles' => ['vactory_news' => 'vactory_news'],
          'negate' => FALSE,
          'context_mapping' => [
            'entity' => '@node.node_route_context:node',
          ],
        ],
      ],
    ]);

    $block_entity->save();
    $this->createdEntities[] = $block_entity;

    // Test with json api.
    $langcode = $node1->language()->getId();
    $data = $this->fetchNodeJsonApi($node1, $langcode);

    $blocks = $data['data']['attributes']['internal_blocks'] ?? [];

    $this->assertIsArray($blocks, 'internal_blocks should be an array');
    $this->assertNotEmpty($blocks, 'internal_blocks should not be empty');

    // Find the created block.
    $target = NULL;

    foreach ($blocks as $block) {
      if (($block['id'] ?? NULL) === 'cross_content_widget_test') {
        $target = $block;
        break;
      }
    }

    $this->assertNotNull(
      $target,
      'A block with id "cross_content_widget_test" should exist.'
    );

    $this->assertArrayHasKey('content', $target, 'The block must contain a "content" key.');
    $this->assertIsArray($target['content'], '"content" key must be an array.');
    $this->assertArrayHasKey('widget_id', $target['content'], '"content" must contain "widget_id".');
    $this->assertEquals($widget_id, $target['content']['widget_id'], '"widget_id" value is incorrect.');
    $this->assertArrayHasKey('widget_data', $target['content'], '"content" must contain "widget_data".');
    $widgetDataJson = $target['content']['widget_data'];
    $this->assertIsString($widgetDataJson, '"widget_data" must be a JSON string.');
    $decoded = json_decode($widgetDataJson, TRUE);
    $this->assertNotNull($decoded, '"widget_data" must be valid JSON.');
    $this->assertIsArray($decoded, 'Decoded widget_data must be an array.');

    $this->assertArrayHasKey('components', $decoded, 'JSON API must contain "components".');
    $this->assertIsArray($decoded['components'], '"components" must be an array.');
    $this->assertNotEmpty($decoded['components'], '"components" cannot be empty.');
    $collection = $decoded['components'][0]['collection']['data'] ?? NULL;
    $this->assertNotNull($collection, 'JSON API collection data must exist.');
    $this->assertArrayHasKey('data', $collection, 'JSON API must contain data array.');
    $this->assertIsArray($collection['data'], 'JSON API data must be an array.');
    $this->assertCount(2, $collection['data'], 'JSON API must return exactly 2 vactory_news nodes.');
    $allowedNids = [
      (int) $node3->id(),
      (int) $node4->id(),
      (int) $node5->id(),
    ];

    $retrievedNids = [];
    foreach ($collection['data'] as $item) {
      $this->assertEquals('node--vactory_news', $item['type'], 'Each returned item must be of type node--vactory_news.');
      $this->assertArrayHasKey('attributes', $item, 'JSON API item must contain attributes.');
      $this->assertArrayHasKey('drupal_internal__nid', $item['attributes'], 'Node must contain drupal_internal__nid attribute.');
      $retrievedNids[] = (int) $item['attributes']['drupal_internal__nid'];
    }

    foreach ($retrievedNids as $nid) {
      $this->assertContains($nid, $allowedNids, "Node nid $nid is not in allowed list.");
    }
  }

  /**
   * Test cross content by related content.
   */
  public function testCrossContentByRelatedContent() {
    // Enable cross content for news.
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.enabling', TRUE);
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.taxonomy_field', 'field_vactory_news_theme');
    $this->modifyConfigValue('node.type.vactory_news', 'third_party_settings.vactory_cross_content.nombre_elements', '3');

    // Load vactory_news_theme vocab.
    $vocabulary = Vocabulary::load('vactory_news_theme');

    // Create term.
    $term = $this->createTerm($vocabulary);

    // Create nodes (news).
    $node1 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 1',
      'status' => 1,
      'field_vactory_news_theme' => $term->id(),
    ]);

    $node2 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 2',
      'status' => 1,
      'field_vactory_news_theme' => $term->id(),
    ]);

    $node3 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 3',
      'status' => 1,
      'field_vactory_news_theme' => $term->id(),
    ]);

    $node4 = $this->createNode([
      'type' => 'vactory_news',
      'title' => 'News test Cross content 4',
      'status' => 1,
      'field_vactory_news_theme' => $term->id(),
      'field_contenu_lie' => "{$node3->id()} {$node2->id()}",
    ]);

    // Prepare the DF.
    $df_name = 'test-cross-content';
    $widget_id = $this->createVolatileDf(self::COLLECTION_SETTING, $df_name);

    // Create a JSON API Collection paragraph.
    $widget_data = [
      [
        'collection' => self::COLLECTION_SETTING['fields']['collection']['options']['#default_value'],
      ],
    ];

    $block_content = BlockContent::create([
      'type' => 'vactory_block_component',
      'info' => 'Cross content widget test',
      'block_machine_name' => 'cross_content_widget_test',
      'field_dynamic_block_components' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);
    $block_content->save();
    $this->createdEntities[] = $block_content;

    $block_entity = Block::create([
      'id' => 'cross_content_widget_test',
      'theme' => 'vactory',
      'region' => 'footer',
      'plugin' => 'block_content:' . $block_content->uuid(),
      'settings' => [
        'id' => 'block_content:' . $block_content->uuid(),
        'label' => 'Cross content widget test',
        'label_display' => TRUE,
        'provider' => 'block_content',
        'status' => 1,
      ],
      'visibility' => [
        'entity_bundle:node' => [
          'id' => 'entity_bundle:node',
          'bundles' => ['vactory_news' => 'vactory_news'],
          'negate' => FALSE,
          'context_mapping' => [
            'entity' => '@node.node_route_context:node',
          ],
        ],
      ],
    ]);

    $block_entity->save();
    $this->createdEntities[] = $block_entity;

    // Test with json api.
    $langcode = $node4->language()->getId();
    $data = $this->fetchNodeJsonApi($node4, $langcode);

    $blocks = $data['data']['attributes']['internal_blocks'] ?? [];

    $this->assertIsArray($blocks, 'internal_blocks should be an array');
    $this->assertNotEmpty($blocks, 'internal_blocks should not be empty');

    // Find the created block.
    $target = NULL;

    foreach ($blocks as $block) {
      if (($block['id'] ?? NULL) === 'cross_content_widget_test') {
        $target = $block;
        break;
      }
    }

    $this->assertNotNull(
      $target,
      'A block with id "cross_content_widget_test" should exist.'
    );

    $this->assertArrayHasKey('content', $target, 'The block must contain a "content" key.');
    $this->assertIsArray($target['content'], '"content" key must be an array.');
    $this->assertArrayHasKey('widget_id', $target['content'], '"content" must contain "widget_id".');
    $this->assertEquals($widget_id, $target['content']['widget_id'], '"widget_id" value is incorrect.');
    $this->assertArrayHasKey('widget_data', $target['content'], '"content" must contain "widget_data".');
    $widgetDataJson = $target['content']['widget_data'];
    $this->assertIsString($widgetDataJson, '"widget_data" must be a JSON string.');
    $decoded = json_decode($widgetDataJson, TRUE);
    $this->assertNotNull($decoded, '"widget_data" must be valid JSON.');
    $this->assertIsArray($decoded, 'Decoded widget_data must be an array.');

    $this->assertArrayHasKey('components', $decoded, 'JSON API must contain "components".');
    $this->assertIsArray($decoded['components'], '"components" must be an array.');
    $this->assertNotEmpty($decoded['components'], '"components" cannot be empty.');
    $collection = $decoded['components'][0]['collection']['data'] ?? NULL;
    $this->assertNotNull($collection, 'JSON API collection data must exist.');
    $this->assertArrayHasKey('data', $collection, 'JSON API must contain data array.');
    $this->assertIsArray($collection['data'], 'JSON API data must be an array.');
    $this->assertCount(2, $collection['data'], 'JSON API must return exactly 2 vactory_news nodes.');
    $allowedNids = [
      (int) $node2->id(),
      (int) $node3->id(),
    ];

    $retrievedNids = [];
    foreach ($collection['data'] as $item) {
      $this->assertEquals('node--vactory_news', $item['type'], 'Each returned item must be of type node--vactory_news.');
      $this->assertArrayHasKey('attributes', $item, 'JSON API item must contain attributes.');
      $this->assertArrayHasKey('drupal_internal__nid', $item['attributes'], 'Node must contain drupal_internal__nid attribute.');
      $retrievedNids[] = (int) $item['attributes']['drupal_internal__nid'];
    }

    foreach ($retrievedNids as $nid) {
      $this->assertContains($nid, $allowedNids, "Node nid $nid is not in allowed list.");
    }
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
