<?php

namespace Drupal\Tests\vactory_dynamic_field\Functional;

use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Random;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test Dynamic Field widget with file field type.
 *
 * @group vactory_dynamic_field
 */
class FileFieldTest extends ExistingSiteBase {

  /**
   * Widget settings with file field.
   */
  const WIDGET_SETTINGS = [
    'name' => 'Test File Widget',
    'multiple' => FALSE,
    'category' => 'Test',
    'enabled' => TRUE,
    'fields' => [
      'title' => [
        'type' => 'text',
        'label' => 'Title',
      ],
      'file' => [
        'type' => 'file',
        'label' => 'File',
      ],
    ],
  ];

  const DF_CREATOR_MODULE = 'vactory_dynamic_field_volatile';

  /**
   * Track if we installed the module during test.
   *
   * @var bool
   */
  protected $moduleInstalledDuringTest = FALSE;

  /**
   * Track created DF files for cleanup.
   *
   * @var array
   */
  protected $createdDfFiles = [];

  /**
   * Track created media for cleanup.
   *
   * @var array
   */
  protected $createdMedia = [];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->ensureModuleInstalled('vactory_dynamic_field');
    $this->ensureModuleInstalled(self::DF_CREATOR_MODULE);
    $this->ensureModuleInstalled('vactory_page');
    $this->ensureModuleInstalled('paragraphs');
    $this->ensureModuleInstalled('media');

    \Drupal::service("router.builder")->rebuild();
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    if (!empty($this->createdMedia)) {
      $media_storage = \Drupal::entityTypeManager()->getStorage('media');
      $file_storage = \Drupal::entityTypeManager()->getStorage('file');

      foreach ($this->createdMedia as $media_id) {
        $media = $media_storage->load($media_id);
        if ($media) {
          $file_id = NULL;
          if ($media->hasField('field_media_file') && !$media->get('field_media_file')->isEmpty()) {
            $file_id = $media->get('field_media_file')->target_id;
          }

          $media->delete();

          if ($file_id) {
            $file = $file_storage->load($file_id);
            if ($file) {
              $file_uri = $file->getFileUri();
              $file->delete();

              if (file_exists($file_uri)) {
                \Drupal::service('file_system')->delete($file_uri);
              }
            }
          }
        }
      }
    }

    foreach ($this->createdDfFiles as $dfPath) {
      if (file_exists($dfPath)) {
        $this->removeDirectory($dfPath);
      }
    }

    if ($this->moduleInstalledDuringTest) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->uninstall([self::DF_CREATOR_MODULE]);
    }

    parent::tearDown();
  }

  /**
   * Test file field in dynamic field widget via JSON:API.
   */
  public function testFileFieldWidget(): void {
    $df_name = 'test-file-widget-' . time();
    $this->writeDfFile(self::WIDGET_SETTINGS, $df_name);

    $random = new Random();
    $file_media = $this->createFileMedia($random);
    $this->createdMedia[] = $file_media->id();

    $widget_data = [
      [
        'title' => 'Test File Widget',
        'file' => [
          $random->machineName(32) => [
            'selection' => [
              [
                'remove_button' => 'Remove',
                'target_id' => (string) $file_media->id(),
                'weight' => 0,
              ],
            ],
            'open_button' => 'Add media',
            'media_library_selection' => '',
            'media_library_update_widget' => 'Update widget',
          ],
        ],
      ],
    ];

    $widget_id = implode(':', [self::DF_CREATOR_MODULE, $df_name]);

    $paragraphStorage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $paragraph = $paragraphStorage->create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);
    $paragraph->save();

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
    $parsedUrl = parse_url($this->baseUrl);

    $scheme = $parsedUrl['scheme'] ?? 'http';
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

    $fullUrl = "{$scheme}://{$host}{$port}/{$langcode}/api/node/vactory_page/{$node->uuid()}?include=field_vactory_paragraphs";

    $this->drupalGet($fullUrl);
    $this->assertSession()->statusCodeEquals(200);

    $json = json_decode($this->getSession()->getPage()->getContent(), TRUE);

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
    $this->assertNotEmpty($widget_data_decoded['components'], 'Components array is not empty.');

    $component = $widget_data_decoded['components'][0] ?? [];
    $this->assertNotEmpty($component, 'Component data should not be empty.');

    $this->assertArrayHasKey('file', $component, 'File field exists.');
    $this->assertIsArray($component['file'], 'File field is an array.');
    $this->assertNotEmpty($component['file'], 'File field is not empty.');

    $file = $component['file'][0];
    $this->assertArrayHasKey('_default', $file, 'File has _default URL.');
    $this->assertNotEmpty($file['_default'], 'File _default URL is not empty.');

    $this->assertStringContainsString('.pdf', $file['_default'], 'File URL contains .pdf extension.');

    $paragraph->delete();
  }

  /**
   * Create a file media entity.
   *
   * @param \Drupal\Component\Utility\Random $random
   *   Random utility.
   *
   * @return \Drupal\media\Entity\Media
   *   The created media entity.
   */
  protected function createFileMedia(Random $random): Media {
    $unique_id = $random->machineName(8);

    $pdf_content = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000056 00000 n\n0000000115 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n210\n%%EOF";

    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->writeData(
      $pdf_content,
      "public://test-file-{$unique_id}.pdf",
      FileSystemInterface::EXISTS_REPLACE
    );

    $file_entity = File::load($file->id());
    $file_entity->save();

    $file_media = Media::create([
      'name' => "Test File {$unique_id}",
      'bundle' => 'file',
      'uid' => \Drupal::currentUser()->id(),
      'field_media_file' => [
        'target_id' => $file_entity->id(),
      ],
    ]);
    $file_media->setPublished()->save();

    return $file_media;
  }

  /**
   * Ensure a module is installed, track if we installed it.
   *
   * @param string $module_name
   *   The module name to install.
   */
  protected function ensureModuleInstalled(string $module_name): void {
    $moduleHandler = \Drupal::service('module_handler');

    if (!$moduleHandler->moduleExists($module_name)) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->install([$module_name]);

      if ($module_name === self::DF_CREATOR_MODULE) {
        $this->moduleInstalledDuringTest = TRUE;
      }
    }
  }

  /**
   * Write DF file and track for cleanup.
   *
   * @param array $content
   *   The widget settings.
   * @param string $name
   *   The widget name.
   *
   * @return bool
   *   TRUE if file was written successfully.
   */
  protected function writeDfFile(array $content, $name): bool {
    $yaml_config = Yaml::encode($content);
    $dest_uri = 'private://volatile-df';
    $dest_df_uri = $dest_uri . '/' . $name;
    
    if (!file_exists($dest_df_uri)) {
      mkdir($dest_df_uri, 0777, TRUE);
    }

    $filepath = \Drupal::service('file_system')->realpath($dest_df_uri . '/settings.yml');
    $printed = file_put_contents($filepath,
    $this->createdDfFiles[] = \Drupal::service('file_system')->realpath($dest_df_uri);

    return (bool) $printed;
  }

  /**
   * Recursively remove a directory and its contents.
   *
   * @param string $dir
   *   The directory path to remove.
   */
  protected function removeDirectory(string $dir): void {
    if (!is_dir($dir)) {
      return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      $path = $dir . DIRECTORY_SEPARATOR . $file;
      if (is_dir($path)) {
        $this->removeDirectory($path);
      }
      else {
        unlink($path);
      }
    }
    rmdir($dir);
  }

}

