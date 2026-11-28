<?php

namespace Drupal\Tests\vactory_image_sitemap\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\vactory_image_sitemap\Services\ImageSitemapGenerator;

/**
 * Tests the collection of the images of the image sitemap.
 *
 * @group vactory_image_sitemap
 */
class ImageSitemapNormalizerTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'field', 'image', 'media', 'node']);

    $this->createMediaType('image', ['id' => 'image']);
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_image_media',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'media'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_image_media',
      'entity_type' => 'node',
      'bundle' => 'page',
      'translatable' => FALSE,
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => ['target_bundles' => ['image' => 'image']],
      ],
    ])->save();
  }

  /**
   * An image in a field that is not translatable is collected.
   */
  public function testImageOfUntranslatableField(): void {
    file_put_contents('public://photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
    $file = File::create([
      'uri' => 'public://photo.png',
      'filename' => 'photo.png',
    ]);
    $file->setPermanent();
    $file->save();
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Photo',
      'field_media_image' => ['target_id' => $file->id()],
    ]);
    $media->save();
    $node = Node::create([
      'type' => 'page',
      'title' => 'Page with image',
      'field_image_media' => ['target_id' => $media->id()],
    ]);
    $node->save();

    $used_images = $this->container->get('database')->select('file_managed', 'fm');
    $used_images->join('file_usage', 'fu', 'fu.fid = fm.fid');
    $used_images = $used_images->fields('fm', ['fid', 'filename', 'uri'])
      ->fields('fu', ['id'])
      ->execute()
      ->fetchAll();
    $this->assertNotEmpty($used_images);

    $language = $this->container->get('language_manager')->getDefaultLanguage();
    $this->assertInstanceOf(LanguageInterface::class, $language);
    $context = [];
    ImageSitemapGenerator::imageSitemapNormalizerBatchProcess(
      $language,
      [['field_name' => 'field_image_media', 'translatable' => FALSE]],
      $used_images,
      $context,
    );

    $sitemap = $context['results']['image_sitemap'][$language->getId()] ?? [];
    $this->assertArrayHasKey($node->id(), $sitemap);
    $this->assertSame('photo.png', $sitemap[$node->id()]['images'][0]['image_title']);
  }

}
