<?php

namespace Drupal\vactory_jsonapi_cross_bundles\Tests\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Test l'affichage et l'existence de cross bundels.
 *
 * @group vactory_decoupled
 */
class VactoryCrossBundlesTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected array $modulesToInstall = [
    'jsonapi_cross_bundles',
    'vactory_jsonapi_cross_bundles',
    'vactory_news',
    'vactory_publication',
  ];

  const DEFAULT_COLLECTION_SETTING = [
    'name' => 'Cross bundles example',
    'multiple' => FALSE,
    'category' => 'News',
    'enabled' => TRUE,
    'fields' => [
      'collection' => [
        'type' => 'json_api_cross_bundles',
        'label' => 'JSON:API',
        'options' => [
          '#required' => TRUE,
          '#default_value' => [
            'resource' => [
              'entity_type' => 'node',
              'bundle' => [
                'vactory_news',
                'vactory_publication',
              ],
            ],
            'filters' => [
              'fields[node--vactory_news]=drupal_internal__nid,title',
              'fields[node--vactory_publication]=drupal_internal__nid,title',
              'page[offset]=0',
              'page[limit]=9',
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
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service("router.builder")->rebuild();

    // Create and log in an admin user using DTT helper.
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Create node (news).
    $this->createNode([
      'type' => 'vactory_news',
      'title' => 'news test ' . uniqid(),
      'status' => 1,
    ]);

    // Create node (publication).
    $this->createNode([
      'type' => 'vactory_publication',
      'title' => 'publication test ' . uniqid(),
      'status' => 1,
    ]);
  }

  /**
   * Tester l’affichage du node avec cross Bundles.
   */
  public function testCrossBundles(): void {

    // Prepare the DF.
    $df_name = 'test-cross-bundles-listing';
    $widget_id = $this->createVolatileDf(self::DEFAULT_COLLECTION_SETTING, $df_name);

    // Create a JSON API Collection paragraph.
    $widget_data = [
      [
        'collection' => self::DEFAULT_COLLECTION_SETTING['fields']['collection']['options']['#default_value'],
      ],
    ];

    // Create the paragraph : Vactory_Component.
    $paragraph = $this->createParagraph([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);

    // Create the node (Vactory_Page) : with DTT helper.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Cross bundle listing test ' . time(),
      'status' => 1,
      'moderation_state' => 'published',
      'field_vactory_paragraphs' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);

    // Test with json api.
    $langcode = $node->language()->getId();

    $params = ['include' => 'field_vactory_paragraphs'];
    $data = $this->fetchNodeJsonApi($node, $langcode, 200, $params);

    $component = $data['included'][0]['attributes']['field_vactory_component'];

    // Vérifier que le widget_id est correct.
    $this->assertEquals(
      $widget_id,
      $component['widget_id'],
      'Widget ID should be vactory_news:cross-bundles.'
    );

    $this->assertJson($component['widget_data'], 'Widget data should be valid JSON.');

    // Récupère le widget_data décodé.
    $widgetData = json_decode($component['widget_data'], TRUE);

    // Récupère les items.
    $items = $widgetData['components'][0]['collection']['data']['data'] ?? [];

    // On extrait juste les "type".
    $types = array_column($items, 'type');

    // On s'assure qu'on a bien nos deux types.
    $this->assertContains('node--vactory_news', $types, 'Widget data doit contenir un node vactory_news.');
    $this->assertContains('node--vactory_publication', $types, 'Widget data doit contenir un node vactory_publication.');
  }

}
