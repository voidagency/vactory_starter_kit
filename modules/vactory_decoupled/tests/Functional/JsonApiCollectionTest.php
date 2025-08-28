<?php

namespace Drupal\vactory_decoupled\Functional;

use Drupal\Core\Serialization\Yaml;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Validate JSON API Collection paragraphs in decoupled context.
 *
 * @group vactory_decoupled
 */
class JsonApiCollectionTest extends ExistingSiteBase {

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

  const DF_CREATOR_MODULE = 'vactory_page_import';

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
   * {@inheritDoc}
   */
  protected function setUp(): void {

    parent::setUp();

    // Create and log in an admin user using DTT helper.
    $adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($adminUser);

    // Make sure vactory_page_import is installed.
    $this->ensureModuleInstalled(self::DF_CREATOR_MODULE);
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    // Clean up created DF files.
    foreach ($this->createdDfFiles as $dfPath) {
      if (file_exists($dfPath)) {
        $this->removeDirectory($dfPath);
      }
    }

    // Uninstall module if we installed it during test.
    if ($this->moduleInstalledDuringTest) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->uninstall([self::DF_CREATOR_MODULE]);
    }

    parent::tearDown();
  }

  /**
   * Test JSON API Collection paragraph in a vactory_page.
   */
  public function testJsonApiCollectionParagraph(): void {
    // Prepare the DF.
    $df_name = 'test-news-listing';
    $this->writeDfFile(self::DEFAULT_COLLECTION_SETTING, $df_name);

    // Create a JSON API Collection paragraph.
    $widget_data = [
      [
        'collection' => self::DEFAULT_COLLECTION_SETTING['fields']['collection']['options']['#default_value'],
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

    // Create a vactory_page with the paragraph.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Test JSON API Collection Page',
      'status' => 1,
      'field_vactory_paragraphs' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);

    $langcode = $node->language()->getId();
    $parsedUrl = parse_url($this->baseUrl);

    // Fallbacks in case parts are missing.
    $scheme = $parsedUrl['scheme'] ?? 'http';
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

    // Full URL for JSON:API request.
    $fullUrl = "{$scheme}://{$host}{$port}/{$langcode}/api/node/vactory_page/{$node->uuid()}?include=field_vactory_paragraphs";

    // Fetch the node via JSON:API.
    $this->drupalGet($fullUrl);
    $this->assertSession()->statusCodeEquals(200);

    // Decode response.
    $json = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $data = $json['included'][0]['attributes']['field_vactory_component']['widget_data'];
    // Decode the widget_data JSON.
    $widget_data_decoded = json_decode($data, TRUE);

    // Get the JSON API collection response.
    $collection_response = $widget_data_decoded['components'][0]['collection']['data'] ?? [];

    // Validate JSON API collection response structure.
    $this->assertArrayHasKey('data', $collection_response, 'Collection response should have data key');
    $this->assertIsArray($collection_response['data'], 'Collection data should be an array');

    // Verify we have vactory_news nodes.
    if (!empty($collection_response['data'])) {
      foreach ($collection_response['data'] as $item) {
        $this->assertEquals('node--vactory_news', $item['type'], 'Item should be of type node--vactory_news');
        $this->assertArrayHasKey('id', $item, 'Item should have an id');
        $this->assertArrayHasKey('attributes', $item, 'Item should have attributes');
      }
    }

    // Cleanup.
    $paragraph->delete();
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
      $this->moduleInstalledDuringTest = TRUE;
    }
  }

  /**
   * Write DF file and track for cleanup.
   *
   * @param array $content
   *   The content to write.
   * @param string $name
   *   The DF name.
   *
   * @return bool
   *   TRUE if file was written successfully.
   */
  protected function writeDfFile(array $content, $name): bool {
    $yaml_config = Yaml::encode($content);
    $dest_uri = 'private://imported-pages-df';
    $dest_df_uri = $dest_uri . '/' . $name;

    if (!file_exists($dest_df_uri)) {
      mkdir($dest_df_uri, 0777, TRUE);
    }

    $filepath = \Drupal::service('file_system')->realpath($dest_df_uri . '/settings.yml');
    $printed = file_put_contents($filepath, $yaml_config);

    // Track the directory for cleanup.
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
