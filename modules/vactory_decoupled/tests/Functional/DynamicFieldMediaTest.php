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
      'title' => [
        'type' => 'text',
        'label' => 'Title',
      ],
      'image' => [
        'type' => 'image',
        'label' => 'Image',
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
    ];

    $image_file = $this->createFile($file_values, 50, 50);

    $image_media = $this->createMedia([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_media_image' => [
        'target_id' => $image_file->id(),
      ],
    ]);

    $widget_data = [
      [
        'title' => 'Test Image Widget',
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

}
