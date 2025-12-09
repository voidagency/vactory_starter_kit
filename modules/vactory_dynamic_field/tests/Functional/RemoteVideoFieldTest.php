<?php

namespace Drupal\Tests\vactory_dynamic_field\Functional;

use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Random;
use Drupal\media\Entity\Media;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test Dynamic Field widget with remote_video field type.
 *
 * @group vactory_dynamic_field
 */
class RemoteVideoFieldTest extends ExistingSiteBase {

  /**
   * Widget settings with remote_video field.
   */
  const WIDGET_SETTINGS = [
    'name' => 'Test Remote Video Widget',
    'multiple' => FALSE,
    'category' => 'Test',
    'enabled' => TRUE,
    'fields' => [
      'title' => [
        'type' => 'text',
        'label' => 'Title',
      ],
      'remote_video' => [
        'type' => 'remote_video',
        'label' => 'Remote Video',
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

      foreach ($this->createdMedia as $media_id) {
        $media = $media_storage->load($media_id);
        if ($media) {
          $media->delete();
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
   * Test remote_video field in dynamic field widget via JSON:API.
   */
  public function testRemoteVideoFieldWidget(): void {
    $df_name = 'test-remote-video-widget-' . time();
    $this->writeDfFile(self::WIDGET_SETTINGS, $df_name);

    $random = new Random();
    $remote_video_media = $this->createRemoteVideoMedia($random);
    $this->createdMedia[] = $remote_video_media->id();

    $widget_data = [
      [
        'title' => 'Test Remote Video Widget',
        'remote_video' => [
          $random->machineName(32) => [
            'selection' => [
              [
                'remove_button' => 'Remove',
                'target_id' => (string) $remote_video_media->id(),
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
      'title' => 'Test Remote Video Widget Page',
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

    $this->assertArrayHasKey('remote_video', $component, 'Remote video field exists.');
    $this->assertIsArray($component['remote_video'], 'Remote video field is an array.');
    $this->assertNotEmpty($component['remote_video'], 'Remote video field is not empty.');

    $remote_video = $component['remote_video'];

    $this->assertArrayHasKey('url', $remote_video, 'Remote video has url field.');
    $this->assertNotEmpty($remote_video['url'], 'Remote video URL is not empty.');

    $this->assertStringContainsString('youtube.com', $remote_video['url'], 'Remote video URL contains youtube.com.');

    $this->assertArrayHasKey('thumbnail', $remote_video, 'Remote video has thumbnail.');
    $this->assertIsArray($remote_video['thumbnail'], 'Thumbnail is an array.');
    $this->assertArrayHasKey('uri', $remote_video['thumbnail'], 'Thumbnail has uri.');

    $paragraph->delete();
  }

  /**
   * Create a remote video media entity.
   *
   * @param \Drupal\Component\Utility\Random $random
   *   Random utility.
   *
   * @return \Drupal\media\Entity\Media
   *   The created media entity.
   */
  protected function createRemoteVideoMedia(Random $random): Media {
    $unique_id = $random->machineName(8);

    $video_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

    $remote_video_media = Media::create([
      'name' => "Test Remote Video {$unique_id}",
      'bundle' => 'remote_video',
      'uid' => \Drupal::currentUser()->id(),
      'field_media_oembed_video' => [
        'value' => $video_url,
      ],
    ]);
    $remote_video_media->setPublished()->save();

    return $remote_video_media;
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
    $printed = file_put_contents($filepath, $yaml_config);

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
