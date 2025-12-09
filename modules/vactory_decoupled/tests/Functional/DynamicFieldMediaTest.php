<?php

namespace Drupal\Tests\vactory_decoupled\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Test Dynamic Field widget with image field type.
 *
 * @group vactory_decoupled
 */
class DynamicFieldMediaTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected array $modulesToInstall = [
    'vactory_dynamic_field',
    'vactory_page',
  ];

  /**
   * Widget settings with image field.
   */
  const WIDGET_SETTINGS = [
    'name' => 'Test Image Widget',
    'multiple' => FALSE,
    'category' => 'Test',
    'enabled' => TRUE,
    'fields' => [
      'image' => [
        'type' => 'image',
        'label' => 'Image',
      ],
      'file' => [
        'type' => 'file',
        'label' => 'File',
      ],
    ],
  ];

  /**
   * Test image field in dynamic field widget via JSON:API.
   */
  public function testImageFieldWidget(): void {
    // Prepare the DF.
    $df_name = 'test-image-widget';
    $widget_id = $this->createVolatileDf(self::WIDGET_SETTINGS, $df_name);

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
      'title' => 'Test Image Widget Page',
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

    $this->assertNotNull($json, 'JSON:API response is valid JSON.');
    $this->assertArrayHasKey('data', $json, 'Response contains data key.');
    $this->assertArrayHasKey('included', $json, 'Response includes related entities.');

    $component_data = $json['included'][0]['attributes']['field_vactory_component'];

    $this->assertEquals(
      $widget_id,
      $component_data['widget_id'],
      "Widget ID should be {$widget_id}."
    );

    $this->assertJson($component_data['widget_data'], 'Widget data should be valid JSON.');

    $widget_data_decoded = json_decode($component_data['widget_data'], TRUE);

    $this->assertArrayHasKey('components', $widget_data_decoded, 'Widget data contains components array.');
    $this->assertNotEmpty($widget_data_decoded['components'], 'Components array should not be empty.');

    $component = $widget_data_decoded['components'][0] ?? [];
    $this->assertNotEmpty($component, 'Component data should not be empty.');

    $this->assertArrayHasKey('image', $component, 'Image field does not exist.');
    $this->assertIsArray($component['image'], 'Image field must be an array.');
    $this->assertNotEmpty($component['image'], 'Image field should not be empty.');

    $image = $component['image'][0];
    $this->assertArrayHasKey('_default', $image, 'Image should have _default URL.');
    $this->assertNotEmpty($image['_default'], 'Image _default URL should not be empty.');
    $this->assertStringEndsWith($file_values['filename'], $image['_default'], 'Image default URL is not correct.');

    $this->assertArrayHasKey('meta', $image, 'Image should have meta property.');
    $this->assertArrayHasKey('width', $image['meta'], 'Image  should have width property.');
    $this->assertArrayHasKey('height', $image['meta'], 'Image  should have height property.');

    $width = (int) $image['meta']['width'];
    $height = (int) $image['meta']['height'];

    $this->assertEquals(50, $width, 'Image width must be 50.');
    $this->assertEquals(50, $height, 'Image height must be 50.');
  }

  /**
   * Test file field in dynamic field widget via JSON:API.
   */
  public function testFileFieldWidget(): void {

    // Prepare the DF.
    $df_name = 'test-file-widget';
    $widget_id = $this->createVolatileDf(self::WIDGET_SETTINGS, $df_name);

    $file_values = [
      'uri' => 'public://test_file.txt',
      'filename' => 'test_file.txt',
      'type' => 'document',
    ];

    $file = $this->createFile($file_values);

    $file_media = $this->createMedia([
      'bundle' => 'file',
      'name' => 'Test file media',
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
    ]);

    $widget_data = [
      [
        'file' => [
          uniqid() => [
            'selection' => [
              [
                'target_id' => (string) $file_media->id(),
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
      'title' => 'Test File Widget Page',
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

    $this->assertNotNull($json, 'JSON:API response should be valid JSON.');
    $this->assertArrayHasKey('data', $json, 'Response should contain the "data" key.');
    $this->assertArrayHasKey('included', $json, 'Response should contain the "included" key with related entities.');

    $component_data = $json['included'][0]['attributes']['field_vactory_component'];

    $this->assertEquals(
      $widget_id,
      $component_data['widget_id'],
      "Returned widget_id should match {$widget_id}."
    );

    $this->assertJson($component_data['widget_data'], 'widget_data should be a valid JSON string.');

    $widget_data_decoded = json_decode($component_data['widget_data'], TRUE);
    dump($widget_data_decoded);

    $this->assertArrayHasKey('components', $widget_data_decoded, 'widget_data should contain a "components" key.');
    $this->assertNotEmpty($widget_data_decoded['components'], '"components" array should not be empty.');

    $component = $widget_data_decoded['components'][0] ?? [];
    $this->assertNotEmpty($component, 'Component data should not be empty.');

    $this->assertArrayHasKey('file', $component, 'Component should contain a "file" field.');
    $this->assertIsArray($component['file'], '"file" field should be an array.');
    $this->assertNotEmpty($component['file'], '"file" field should not be empty.');

    $file = $component['file'][0];
    $this->assertArrayHasKey('_default', $file, 'File entry should contain a "_default" URL.');
    $this->assertNotEmpty($file['_default'], 'File "_default" URL should not be empty.');

    $this->assertStringEndsWith(
      'test_file.txt',
      $file['_default'],
      'File URL should end with "test_file.txt".'
    );
  }

}
