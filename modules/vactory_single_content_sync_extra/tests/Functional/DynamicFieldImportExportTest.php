<?php

namespace Drupal\Tests\vactory_decoupled_webform\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Test decoupled Vactory page with WYSIWYG, DF, and assets.
 *
 * @group vactory_decoupled_webform
 */
class DynamicFieldImportExportTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected array $modulesToInstall = [
    'single_content_sync',
    'vactory_single_content_sync_extra',
    'vactory_decoupled',
  ];


  const COLLECTION_SETTING = [
    'name' => 'DF title',
    'multiple' => FALSE,
    'enabled' => TRUE,
    'fields' => [
      'image' => [
        'type' => 'image',
        'label' => 'Image',
      ],
    ],
  ];

  const WYSIWYG_COLLECTION_SETTING = [
    'name' => 'DF title',
    'multiple' => FALSE,
    'enabled' => TRUE,
    'fields' => [
      'content' => [
        'type' => 'text_format',
        'label' => "Content",
        'options' => [
          '#format' => 'full_html',
        ],
      ],
    ],
  ];

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
   * Test dynamic field media export.
   */
  public function testDynamicFieldMediaExport() {
    // Prepare the DF.
    $df_name = 'test-df-title';
    $widget_id = $this->createVolatileDf(self::COLLECTION_SETTING, $df_name);

    $file_values = [
      'uri' => 'public://test_image.jpg',
      'filename' => 'test_image.jpg',
      'type' => 'image',
      'width' => 50,
      'height' => 50,
    ];

    $image_file = $this->createFile($file_values);

    $image_media = $this->createMedia([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_media_image' => [
        'target_id' => $image_file->id(),
      ],
    ]);

    $widget_data = [
      [
        'image' => [
          uniqid() => [
            'selection' => [
              [
                'target_id' => (string) $image_media->id(),
              ],
            ],
          ],
        ],
      ],
    ];

    $paragraph = $this->createParagraph([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);

    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Test dynamic field import page',
      'status' => 1,
      'moderation_state' => 'published',
      'field_vactory_paragraphs' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);

    $content_exporter = \Drupal::service('single_content_sync.exporter');
    $exported = $content_exporter->getFieldValue($node->get('field_vactory_paragraphs'));
    $widget_data = $exported[0]['custom_fields']['field_vactory_component'][0]['widget_data'];
    $decoded_data = json_decode($widget_data, TRUE);
    $decoded_data = reset($decoded_data);

    $this->assertIsArray($decoded_data['image']);

    $this->assertArrayHasKey('single_content_sync_media', $decoded_data['image']);
    $scsm = $decoded_data['image']['single_content_sync_media'];

    $this->assertArrayHasKey('exported_media', $scsm);
    $this->assertIsArray($scsm['exported_media']);
    $this->assertNotEmpty($scsm['exported_media']);

    $exported_media = $scsm['exported_media'][0];

    $this->assertArrayHasKey('uuid', $exported_media);
    $this->assertEquals($image_media->uuid(), $exported_media['uuid']);

    $this->assertArrayHasKey('entity_type', $exported_media);
    $this->assertEquals('media', $exported_media['entity_type']);

    $this->assertArrayHasKey('bundle', $exported_media);
    $this->assertEquals('image', $exported_media['bundle']);
  }

  /**
   * Test dynamic field wysiwyg export.
   */
  public function testDynamicFieldWysiwygExport() {
    // Prepare the DF.
    $df_name = 'test-df-wysiwyg';
    $widget_id = $this->createVolatileDf(self::WYSIWYG_COLLECTION_SETTING, $df_name);

    $file_values = [
      'uri' => 'public://test_image.jpg',
      'filename' => 'test_image.jpg',
      'type' => 'image',
      'width' => 50,
      'height' => 50,
    ];

    $file = $this->createFile($file_values);
    $uri = $file->getFileUri();
    $path = \Drupal::service('file_system')->realpath($uri);
    $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);

    // Récupérer les dimensions dynamiquement.
    [$width, $height] = getimagesize($path);

    // Construire le HTML du contenu WYSIWYG.
    $contentHtml = sprintf(
      '<p><strong>titre content bold</strong></p><p><em>titre content bold</em></p><img data-entity-uuid="%s" data-entity-type="file" src="%s" width="%d" height="%d" alt="test decortive de l\'image">',
      $file->uuid(),
      $url,
      $width,
      $height
    );

    $widget_data = [
      [
        'content' => [
          'value' => $contentHtml,
          'format' => 'full_html',
        ],
      ],
    ];

    $paragraph = $this->createParagraph([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);

    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Test dynamic field wysiwyg import page',
      'status' => 1,
      'moderation_state' => 'published',
      'field_vactory_paragraphs' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);

    $content_exporter = \Drupal::service('single_content_sync.exporter');
    $exported = $content_exporter->getFieldValue($node->get('field_vactory_paragraphs'));
    $widget_data = $exported[0]['custom_fields']['field_vactory_component'][0]['widget_data'];
    $decoded_data = json_decode($widget_data, TRUE);
    $decoded_data = reset($decoded_data);

    $this->assertIsArray($decoded_data['content']);

    $this->assertArrayHasKey('embed_entities', $decoded_data['content']);
    $embed_entities = $decoded_data['content']['embed_entities'];
    $this->assertIsArray($embed_entities);
    $this->assertCount(1, $embed_entities);
    $embed_entity = reset($embed_entities);

    $this->assertArrayHasKey('uuid', $embed_entity);
    $this->assertEquals($file->uuid(), $embed_entity['uuid']);

    $this->assertArrayHasKey('entity_type', $embed_entity);
    $this->assertEquals('file', $embed_entity['entity_type']);

    $this->assertArrayHasKey('bundle', $embed_entity);
    $this->assertEquals('image', $embed_entity['bundle']);

    $this->assertArrayHasKey('base_fields', $embed_entity);
  }

}
