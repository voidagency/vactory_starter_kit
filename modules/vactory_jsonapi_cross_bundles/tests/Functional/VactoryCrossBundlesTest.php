<?php

namespace Drupal\vactory_jsonapi_cross_bundles\Tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Test l'affichage et l'existence de cross bundels.
 *
 * @group vactory_decoupled
 */
class VactoryCrossBundlesTest extends ExistingSiteBase {

  const DF_CREATOR_MODULE = 'vactory_dynamic_field_volatile';

  /**
   * Track created DF files for cleanup.
   *
   * @var array
   */
  protected $createdDfFiles = [];

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
              'sort[date][path]=field_vactory_date',
              'sort[date][direction]=DESC',
            ],
          ],
        ],
      ],
    ],
  ];

  /**
   * Track modules installed during the test.
   *
   * @var string[]
   */
  protected array $modulesInstalledDuringTest = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Assurer que les modules sont installés.
    $this->ensureModuleInstalled('jsonapi_cross_bundles');
    $this->ensureModuleInstalled('vactory_jsonapi_cross_bundles');
    $this->ensureModuleInstalled('vactory_news');
    $this->ensureModuleInstalled('vactory_publication');
    $this->ensureModuleInstalled(self::DF_CREATOR_MODULE);

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
    $this->writeDfFile(self::DEFAULT_COLLECTION_SETTING, $df_name);

    // Create a JSON API Collection paragraph.
    $widget_data = [
      [
        'collection' => self::DEFAULT_COLLECTION_SETTING['fields']['collection']['options']['#default_value'],
      ],
    ];
    $widget_id = implode(':', [self::DF_CREATOR_MODULE, $df_name]);

    // Create the paragraph : Vactory_Component.
    $paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ]);
    $paragraph->save();

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
    $parsedUrl = parse_url($this->baseUrl);

    // Fallbacks.
    $scheme = $parsedUrl['scheme'] ?? 'http';
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

    // URL finale.
    $fullUrl = "{$scheme}://{$host}{$port}/{$langcode}/api/node/vactory_page/{$node->uuid()}?include=field_vactory_paragraphs";

    $this->drupalGet($fullUrl);
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

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

    // Cleanup.
    $paragraph->delete();
  }

  /**
   * Ensure a module is installed and track if we installed it during the test.
   */
  protected function ensureModuleInstalled(string $module_name): void {
    $moduleHandler = \Drupal::service('module_handler');
    $moduleInstaller = \Drupal::service('module_installer');

    if (!$moduleHandler->moduleExists($module_name)) {
      $moduleInstaller->install([$module_name]);
      $this->modulesInstalledDuringTest[] = $module_name;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Désinstaller les modules installés pendant le test.
    if (!empty($this->modulesInstalledDuringTest)) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->uninstall($this->modulesInstalledDuringTest);
    }
    parent::tearDown();
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
    $dest_uri = 'private://volatile-df';
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
