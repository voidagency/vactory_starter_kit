<?php

namespace Drupal\vactory_decoupled_cross_content\Functional;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Validate the computed field internal_blocks.
 *
 * @group vactory_decoupled
 */
class InternalBlocksTest extends VactoryExistingSiteBase {

  const COLLECTION_SETTING = [
    'name' => 'DF title',
    'multiple' => FALSE,
    'enabled' => TRUE,
    'fields' => [
      'title' => [
        'type' => 'text',
        'label' => 'Title',
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = [
    'vactory_decoupled',
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
   * Test basic internal_blocks.
   */
  public function testInternalBlocks() {
    // Prepare the DF.
    $df_name = 'test-df-title';
    $widget_id = $this->createVolatileDf(self::COLLECTION_SETTING, $df_name);

    // Create a JSON API Collection paragraph.
    $widget_data = [
      [
        'title' => 'my title',
      ],
    ];

    $block_content = BlockContent::create([
      'type' => 'vactory_block_component',
      'info' => 'Internal block test',
      'block_machine_name' => 'internal_block_test',
      'field_dynamic_block_components' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);
    $block_content->save();
    $this->createdEntities[] = $block_content;

    $block_entity = Block::create([
      'id' => 'internal_block_test',
      'theme' => 'vactory',
      'region' => 'footer',
      'plugin' => 'block_content:' . $block_content->uuid(),
      'settings' => [
        'id' => 'block_content:' . $block_content->uuid(),
        'label' => 'Internal block test',
        'label_display' => TRUE,
        'provider' => 'block_content',
        'status' => 1,
      ],
      'visibility' => [],
    ]);

    $block_entity->save();
    $this->createdEntities[] = $block_entity;

    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Internal block test Page',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    // Test with json api.
    $langcode = $node->language()->getId();
    $data = $this->fetchNodeJsonApi($node, $langcode);

    $blocks = $data['data']['attributes']['internal_blocks'] ?? [];

    $this->assertIsArray($blocks, 'internal_blocks should be an array');
    $this->assertNotEmpty($blocks, 'internal_blocks should not be empty');

    // Find the created block.
    $target = NULL;

    foreach ($blocks as $block) {
      if (($block['id'] ?? NULL) === 'internal_block_test') {
        $target = $block;
        break;
      }
    }

    $this->assertNotNull(
      $target,
      'A block with id "internal_block_test" should exist.'
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

    $this->assertArrayHasKey('components', $decoded, 'The block must contain "components".');
    $this->assertIsArray($decoded['components'], '"components" must be an array.');
    $this->assertNotEmpty($decoded['components'], '"components" cannot be empty.');
    $data = $decoded['components'][0] ?? NULL;
    $this->assertNotNull($data, 'DF data must exist.');
    $this->assertArrayHasKey('title', $data, 'JSON API must contain data array.');
    $this->assertEquals('my title', $data['title'], 'The title value is not correct.');
  }

  /**
   * Test internal_blocks visibility based on path condition.
   */
  public function testInternalBlocksVisibilityByPath() {
    // 1. Create DF widget.
    $df_name = 'test-visibility-df';
    $widget_id = $this->createVolatileDf(self::COLLECTION_SETTING, $df_name);

    $widget_data = [
      [
        'title' => 'visible block',
      ],
    ];

    // 2. Create Block Content.
    $block_content = BlockContent::create([
      'type' => 'vactory_block_component',
      'info' => 'Visibility test block',
      'block_machine_name' => 'visibility_test_block',
      'field_dynamic_block_components' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);
    $block_content->save();
    $this->createdEntities[] = $block_content;

    // 3. Create two pages X and Y.
    $page_x = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Page test X',
      'status' => 1,
      'moderation_state' => 'published',
    ]);
    $page_y = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Page test Y',
      'status' => 1,
      'moderation_state' => 'published',
    ]);

    // Their paths (usually /node/N).
    $path_x = '/node/' . $page_x->id();

    // 4. Create Block with visibility = only page X.
    $block_entity = Block::create([
      'id' => 'visibility_test_block',
      'theme' => 'vactory',
      'region' => 'content',
      'plugin' => 'block_content:' . $block_content->uuid(),
      'settings' => [
        'id' => 'block_content:' . $block_content->uuid(),
        'label' => 'Visibility test block',
        'label_display' => TRUE,
        'provider' => 'block_content',
        'status' => 1,
      ],
      'visibility' => [
        'request_path' => [
          'id' => 'request_path',
          'pages' => $path_x,
          'negate' => FALSE,
          'context_mapping' => [],
        ],
      ],
    ]);

    $block_entity->save();
    $this->createdEntities[] = $block_entity;

    // 5. Fetch JSON:API for page X.
    $langcode = $page_x->language()->getId();
    $data_x = $this->fetchNodeJsonApi($page_x, $langcode);

    $blocks_x = $data_x['data']['attributes']['internal_blocks'] ?? [];

    // Block SHOULD appear on page X.
    $found_on_x = FALSE;
    foreach ($blocks_x as $b) {
      if (($b['id'] ?? NULL) === 'visibility_test_block') {
        $found_on_x = TRUE;
        break;
      }
    }
    $this->assertTrue($found_on_x, 'The block should be visible on Page X.');

    // 6. Fetch JSON:API for page Y.
    $langcode = $page_y->language()->getId();
    $data_y = $this->fetchNodeJsonApi($page_y, $langcode);

    $blocks_y = $data_y['data']['attributes']['internal_blocks'] ?? [];
    // Block SHOULD NOT appear on page Y.
    $found_on_y = FALSE;
    foreach ($blocks_y as $b) {
      if (($b['id'] ?? NULL) === 'visibility_test_block') {
        $found_on_y = TRUE;
        break;
      }
    }

    $this->assertFalse($found_on_y, 'The block should NOT be visible on Page Y.');
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
