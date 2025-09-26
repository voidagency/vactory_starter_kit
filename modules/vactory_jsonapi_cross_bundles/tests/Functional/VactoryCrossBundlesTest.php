<?php

namespace Drupal\vactory_jsonapi_cross_bundles\Tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\user\Entity\User;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Test l'affichage et l'existence de cross bundels.
 *
 * @group vactory_decoupled
 */
class VactoryCrossBundlesTest extends ExistingSiteBase {

  /**
   * Admin password used for authentication.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $admin;

  /**
   * Node used for testing.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected Node $node;

  /**
   * Paragraph user for testing.
   *
   * @var \Drupal\paragraphs\Entity\Paragraph
   */
  protected Paragraph $paragraph;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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

    // Retrieve core services.
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->httpClient = \Drupal::httpClient();

    // Create and log in an admin user using DTT helper.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    // Create the paragraph : Vactory_Component.
    $this->paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => 'vactory_news:cross-bundles',
        'widget_data' => json_encode($this->setWidgetData()),
      ],
    ]);
    $this->paragraph->save();

    $paragraphStorage = $this->entityTypeManager->getStorage('paragraph');

    // Create the node (Vactory_Page) : with DTT helper.
    $this->node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Page_avec Cross content_' . time(),
      'status' => 1,
      'moderation_state' => 'published',
      'field_vactory_paragraphs' => [
        [
          'target_id' => $this->paragraph->id(),
          'target_revision_id' => $paragraphStorage->getLatestRevisionId($this->paragraph->id()),
        ],
      ],
    ]);
  }

  /**
   * Tester l’affichage du node avec cross Bundles.
   */
  public function testCrossBundles(): void {
    // Test with json api.
    $langcode = $this->node->language()->getId();
    $parsedUrl = parse_url($this->baseUrl);

    // Fallbacks.
    $scheme = $parsedUrl['scheme'] ?? 'http';
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

    // URL finale.
    $fullUrl = "{$scheme}://{$host}{$port}/{$langcode}/api/node/vactory_page/{$this->node->uuid()}?include=field_vactory_paragraphs";

    $this->drupalGet($fullUrl);
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $component = $data['included'][0]['attributes']['field_vactory_component'];

    // Vérifier que le widget_id est correct.
    $this->assertEquals(
      'vactory_news:cross-bundles',
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

  /**
   * Helper générer le widget_data (vactory_news:cross-bundles).
   *
   * @return array
   *   Tableau de configuration du widget.
   */
  protected function setWidgetData(): array {
    return [
      "0" => [
        "collection" => [
          "resource" => [
            "entity_type" => "node",
            "bundle" => [
              "vactory_news" => "vactory_news",
              "vactory_publication" => "vactory_publication",
              "vactory_academy" => 0,
              "vactory_blog" => 0,
              "vactory_event" => 0,
              "vactory_faq" => 0,
              "vactory_forum" => 0,
              "vactory_glossary" => 0,
              "vactory_help_center" => 0,
              "vactory_job_ads" => 0,
              "vactory_mediatheque" => 0,
              "vactory_multivers" => 0,
              "vactory_page" => 0,
              "vactory_press_kit" => 0,
              "vactory_press_release" => 0,
              "vactory_seo" => 0,
              "vactory_testimonials" => 0,
            ],
            "submit" => "update entity reference",
          ],
          "filters" => [
            "sort[date][path]=field_vactory_date",
            "sort[date][direction]=DESC",
            "page[offset]=0",
            "page[limit]=9",
            "filter[status][value]=1",
            "sort[sort-vactory-date][path]=field_vactory_date",
            "sort[sort-vactory-date][direction]=DESC",
            "fields[node--vactory_news]=drupal_internal__nid,path,title,field_vactory_news_theme,field_vactory_media,field_vactory_excerpt,field_vactory_date,is_flagged,has_flag",
            "fields[taxonomy_term--vactory_news_theme]=tid,name",
            "include=field_vactory_publication_theme,field_vactory_news_theme,field_vactory_media,field_vactory_media.thumbnail",
            "fields[node--vactory_publication]=drupal_internal__nid,path,title,field_vactory_date,field_vactory_media_document,field_vactory_call_to_action,field_vactory_excerpt,field_vactory_media,field_vactory_publication_theme,field_vactory_tags,field_media_file",
            "fields[taxonomy_term--vactory_publication_theme]=tid,name",
            "fields[taxonomy_term--tags]=tid,name",
            "fields[file--document]=filename,uri",
            "fields[media--file]= field_media_file,uri",
            "fields[media--image]=name,thumbnail",
            "fields[file--image]=filename,uri",
          ],
          "entity_queue" => "",
          "entity_queue_field_id" => "",
          "id" => "vactory_news_cross_bundles",
          "cache_tags" => "",
          "cache_contexts" => "",
          "vocabularies" => [
            "faq_section" => 0,
            "locator_category" => 0,
            "locator_city" => 0,
            "locator_country" => 0,
            "mediatheque_theme_albums" => 0,
            "mediatheque_types" => 0,
            "medium_year" => 0,
            "multivers" => 0,
            "press_kit_theme" => 0,
            "press_release_theme" => 0,
            "users_groups" => 0,
            "vactory_academy_themes" => 0,
            "vactory_blog_categories" => 0,
            "vactory_blog_tags" => 0,
            "vactory_cross_content_taxonomy" => 0,
            "vactory_event_category" => 0,
            "vactory_event_citys" => 0,
            "vactory_forums_thematic" => 0,
            "vactory_forum_room" => 0,
            "vactory_glossary" => 0,
            "vactory_help_center" => 0,
            "vactory_job_ads_city" => 0,
            "vactory_job_ads_contract" => 0,
            "vactory_job_ads_profession" => 0,
            "vactory_news_theme" => 0,
            "vactory_publication_theme" => 0,
            "vactory_testimonials_profils" => 0,
          ],
        ],
        "pending_content" => [],
      ],
    ];
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

    // Supprimer paragraph ajouteé pendant le test.
    if (isset($this->paragraph) && $this->paragraph) {
      $this->paragraph->delete();
    }
    // Désinstaller les modules installés pendant le test.
    if (!empty($this->modulesInstalledDuringTest)) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->uninstall($this->modulesInstalledDuringTest);
    }
    parent::tearDown();
  }

}
