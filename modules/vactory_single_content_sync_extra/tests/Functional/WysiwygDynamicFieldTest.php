<?php

namespace Drupal\Tests\vactory_decoupled_webform\Functional;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\media\Entity\Media;
use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\file\FileRepository;
use Drupal\Core\File\FileSystemInterface;

/**
 * Test decoupled Vactory page with WYSIWYG, DF, and assets.
 *
 * @group vactory_decoupled_webform
 */
class WysiwygDynamicFieldTest extends ExistingSiteBase {

  /**
   * Service for managing file entities in Drupal.
   *
   * @var \Drupal\file\FileRepository
   */
  protected FileRepository $fileRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Assurer que les modules sont installés.
    $this->ensureModuleInstalled('single_content_sync');
    $this->ensureModuleInstalled('vactory_single_content_sync_extra');
    $this->ensureModuleInstalled('vactory_decoupled');

    // Create and log in an admin user using DTT helper.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    // Créer les médias de test.
    $this->imageMedia = $this->createMediaEntity(__DIR__ . '/../../assets/imageWysiwygDF.jpg', 'CTA Image');
    $this->footerMedia = $this->createMediaEntity(__DIR__ . '/../../assets/imageFooterDF.jpg', 'Footer Logo');

    // Créer les paragraphes det test.
    $this->paragraphWysiwygDynamicField = $this->createWysiwygParagraph($this->imageMedia);
    $this->paragraphFooter = $this->createFooterParagraph($this->footerMedia);

    // Create the node (Vactory_Page) : with DTT helper.
    $paragraphStorage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $this->node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Page PHPUnit complète',
      'status' => 1,
      'field_vactory_paragraphs' => [
        [
          'target_id' => $this->paragraphWysiwygDynamicField->id(),
          'target_revision_id' => $paragraphStorage->getLatestRevisionId($this->paragraphWysiwygDynamicField->id()),
        ],
        [
          'target_id' => $this->paragraphFooter->id(),
          'target_revision_id' => $paragraphStorage->getLatestRevisionId($this->paragraphFooter->id()),
        ],
      ],
    ]);
  }

  /**
   * Test the decoupled WYSIWYG dynamic field via JSON API.
   *
   * Checks that:
   * - The node contains exactly 2 paragraphs.
   * - Each paragraph has valid widget_data.
   * - WYSIWYG content is present and non-empty.
   * - CTA elements have both title and URL.
   * - Images contain the expected _default/_original structure.
   */
  public function testDecoupledWysiwygDynamicField(): void {
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

    // Decode the JSON into an associative array.
    $fullData = json_decode($response, TRUE);

    // Access the 'included' part.
    $includedData = $fullData['included'] ?? [];

    $this->assertCount(2, $includedData, 'Le node doit contenir exactement 2 paragraphes.');

    // Loop through each paragraph.
    foreach ($includedData as $item) {
      $componentField = $item['attributes']['field_vactory_component'] ?? [];

      // Decode widget_data JSON string.
      $widgetData = json_decode($componentField['widget_data'] ?? '{}', TRUE);

      // Asserts.
      $this->assertNotEmpty($widgetData, 'Widget data should not be empty');
      $this->assertArrayHasKey('components', $widgetData, 'Widget data must contain components');

      foreach ($widgetData['components'] as $componentGroup) {
        foreach ($componentGroup as $component) {
          // If it's a Wysiwyg component.
          if (isset($component['content'])) {
            $this->assertArrayHasKey('value', $component['content'], 'Wysiwyg content must have value');
            $this->assertNotEmpty($component['content']['value'], 'Wysiwyg content value should not be empty');
          }

          // If it has a CTA.
          if (isset($component['cta'])) {
            $this->assertArrayHasKey('url', $component['cta'], 'CTA must have a URL');
            $this->assertArrayHasKey('title', $component['cta'], 'CTA must have a title');
          }

          // If it has images.
          if (isset($component['bigImage'])) {
            foreach ($component['bigImage'] as $img) {
              $this->assertArrayHasKey('_default', $img, 'Image must have _default key');
              $this->assertArrayHasKey('_original', $img['_default'], 'Image _default must have _original');
            }
          }
        }
      }
    }
  }

  /**
   * Test the export of WYSIWYG dynamic field data.
   *
   * Verifies the structure of 'original', 'single_content_sync_media',
   * 'exported_media', and 'embed_entities' for each widget and extra_field,
   * ensuring that UUIDs, entity types, bundles.
   */
  public function testProcessWidgetDataExport(): void {
    // Load the field value from the node.
    $paragraphs = $this->node->get('field_vactory_paragraphs')
      ->referencedEntities();

    // Get the plugin instance.
    $plugin_manager = \Drupal::service('plugin.manager.single_content_sync_field_processor');
    /** @var \Drupal\vactory_single_content_sync_extra\Plugin\SingleContentSyncFieldProcessor\WysiwygDynamicField $plugin */
    $plugin = $plugin_manager->createInstance('vactory_component');

    foreach ($paragraphs as $paragraph) {
      // Récupération du champ field_vactory_component.
      $field = $paragraph->get('field_vactory_component');

      // Export du champ via le plugin.
      $exported = $plugin->exportFieldValue($field);

      // Décodage des données JSON exportées.
      $exportedFields = json_decode($exported[0]['widget_data'], TRUE);

      // Parcours de chaque widget.
      foreach ($exportedFields as $key => $widget) {
        foreach (['bigImage', 'smallImage'] as $mediaKey) {
          // Vérification de original.
          if (isset($widget[$mediaKey]['original'])) {
            $original = $widget[$mediaKey]['original'];
            $this->assertIsArray($original);
            foreach ($original as $mediaUuid => $media) {
              $this->assertArrayHasKey('selection', $media);
              $this->assertArrayHasKey('open_button', $media);
              $this->assertArrayHasKey('media_library_update_widget', $media);
            }
          }

          // Vérification des single_content_sync_media -> exported_media.
          if (isset($widget[$mediaKey]['single_content_sync_media'])) {
            $scsm = $widget[$mediaKey]['single_content_sync_media'];
            $this->assertArrayHasKey('exported_media', $scsm);
            $this->assertIsArray($scsm['exported_media']);
            $this->assertNotEmpty($scsm['exported_media']);

            $firstMedia = $scsm['exported_media'][0];
            $this->assertArrayHasKey('uuid', $firstMedia);
            $this->assertArrayHasKey('entity_type', $firstMedia);
            $this->assertEquals('media', $firstMedia['entity_type']);
            $this->assertArrayHasKey('bundle', $firstMedia);

            // Vérification du target_entity si présent.
            if (isset($firstMedia['custom_fields']['field_media_image'][0]['target_entity'])) {
              $targetEntity = $firstMedia['custom_fields']['field_media_image'][0]['target_entity'];
              $this->assertArrayHasKey('uuid', $targetEntity);
              $this->assertEquals('file', $targetEntity['entity_type']);
              $this->assertArrayHasKey('base_fields', $targetEntity);
              $this->assertArrayHasKey('uri', $targetEntity['base_fields']);
            }
          }
        }

        // Vérification des embed_entities dans content.
        foreach (['content'] as $contentKey) {
          if (isset($widget[$contentKey]['embed_entities'])) {
            $embedEntities = $widget[$contentKey]['embed_entities'];
            $this->assertIsArray($embedEntities);

            foreach ($embedEntities as $embed) {
              $this->assertArrayHasKey('uuid', $embed);
              $this->assertArrayHasKey('entity_type', $embed);
              $this->assertContains($embed['entity_type'], ['file', 'media']);
              $this->assertArrayHasKey('bundle', $embed);
              $this->assertArrayHasKey('base_fields', $embed);
              $this->assertArrayHasKey('name', $embed['base_fields']);
            }
          }
        }

        // Vérification des extra_field.
        if (isset($widget['extra_field'])) {
          $extra = $widget['extra_field'];

          // Single_content_sync_media dans logo.
          if (isset($extra['logo']['single_content_sync_media'])) {
            $scsm = $extra['logo']['single_content_sync_media'];
            $this->assertArrayHasKey('exported_media', $scsm);
            $this->assertNotEmpty($scsm['exported_media']);

            $firstMedia = $scsm['exported_media'][0];
            $this->assertArrayHasKey('uuid', $firstMedia);
            $this->assertArrayHasKey('entity_type', $firstMedia);
            $this->assertEquals('media', $firstMedia['entity_type']);
            $this->assertArrayHasKey('bundle', $firstMedia);
          }

          // Embed_entities dans left_content et right_content.
          foreach (['left_content', 'right_content'] as $contentSide) {
            if (isset($extra[$contentSide]['embed_entities'])) {
              $embedEntities = $extra[$contentSide]['embed_entities'];
              $this->assertIsArray($embedEntities);
              foreach ($embedEntities as $embed) {
                $this->assertArrayHasKey('uuid', $embed);
                $this->assertArrayHasKey('entity_type', $embed);
                $this->assertContains($embed['entity_type'], ['file', 'media']);
                $this->assertArrayHasKey('bundle', $embed);
                $this->assertArrayHasKey('base_fields', $embed);
                $this->assertArrayHasKey('name', $embed['base_fields']);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Create a Media entity from a file.
   */
  protected function createMediaEntity(string $filePath, string $name): Media {
    $data = file_get_contents($filePath);

    // Créer l'entité File gérée.
    $fileRepository = \Drupal::service('file.repository');
    $file = $fileRepository->writeData($data, 'public://' . basename($filePath), FileSystemInterface::EXISTS_REPLACE);

    // Créer le Media.
    $media = Media::create([
      'bundle' => 'image',
      'name' => $name,
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => $name,
        'title' => $name,
      ],
      'status' => 1,
    ]);
    $media->save();

    return $media;
  }

  /**
   * Create a Paragraph with a WYSIWYG component and Media Library style images.
   */
  protected function createWysiwygParagraph(Media $imageMedia): Paragraph {
    $file = $imageMedia->field_media_image->entity;
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

    // Créer le tableau widget_data au format Media Library.
    $widgetData = [
      '0' => [
        'bigImage' => [
          $file->uuid() => [
            'selection' => [
              [
                'remove_button' => 'Remove',
                'target_id' => $imageMedia->id(),
                'weight' => 0,
              ],
            ],
            'open_button' => 'Add media',
            'media_library_selection' => '',
            'media_library_update_widget' => 'Update widget',
          ],
        ],
        'smallImage' => [
          $file->uuid() => [
            'selection' => [
              [
                'remove_button' => 'Remove',
                'target_id' => $imageMedia->id(),
                'weight' => 0,
              ],
            ],
            'open_button' => 'Add media',
            'media_library_selection' => '',
            'media_library_update_widget' => 'Update widget',
          ],
        ],
        'title' => 'titre',
        'content' => [
          'value' => $contentHtml,
          'format' => 'full_html',
        ],
        'cta' => [
          'title' => 'Praesent nisl eros',
          'url' => '/node/167',
          'attributes' => [
            'label' => '',
            'class' => '',
            'id' => 'cta-mde3ntg3mzmzotm',
            'target' => '_self',
            'rel' => '',
          ],
        ],
      ],
      'pending_content' => [],
    ];

    // Créer le Paragraph.
    $paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => 'vactory_default:59',
        'widget_data' => json_encode($widgetData),
      ],
    ]);

    $paragraph->save();
    return $paragraph;
  }

  /**
   * Create a Paragraph for a footer with WYSIWYG content and logo.
   */
  protected function createFooterParagraph(Media $footerMedia): Paragraph {
    // Récupérer le fichier associé au média.
    $file = $footerMedia->field_media_image->entity;
    $uri = $file->getFileUri();
    $path = \Drupal::service('file_system')->realpath($uri);
    $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);

    // Dimensions de l’image.
    [$width, $height] = getimagesize($path);

    // Contenu WYSIWYG gauche.
    $contentFooterHtmlLeft = sprintf(
      '<p><em>this is a wysiwig text italyue</em></p>
     <img data-entity-uuid="%s" data-entity-type="file" src="%s" width="%d" height="%d">',
      $file->uuid(),
      $url,
      $width,
      $height
    );

    // Contenu WYSIWYG droit.
    $contentFooterHtmlRight = '<p><strong>this is a wysiwig text bold</strong></p>';

    // Hash utilisé comme clé du logo (comme dans ton JSON).
    $logoKey = md5($file->uuid());

    // Structure widget_data conforme à ton JSON.
    $widgetData = [
      'extra_field' => [
        'enableThemeSwitcher' => 0,
        'logo' => [
          $logoKey => [
            'selection' => [
              [
                'remove_button' => 'Remove',
                'target_id' => (string) $footerMedia->id(),
                'weight' => 0,
              ],
            ],
            'open_button' => 'Add media',
            'media_library_selection' => '',
            'media_library_update_widget' => 'Update widget',
          ],
        ],
        'use_menu' => '',
        'copyrights' => '',
        'left_content' => [
          'value' => $contentFooterHtmlLeft,
          'format' => 'full_html',
        ],
        'right_content' => [
          'value' => $contentFooterHtmlRight,
          'format' => 'full_html',
        ],
      ],
      '0' => [
        'cta_social' => [
          'title' => 'titre',
          'url' => '/node/265',
          'attributes' => [
            'label' => '',
            'class' => '',
            'id' => 'cta-social-' . uniqid(),
            'target' => '_self',
            'rel' => '',
          ],
        ],
        'icon' => '',
        '_weight' => '1',
      ],
      'pending_content' => [],
    ];

    // Création du paragraphe.
    $paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => 'vactory_footer:footer-variant5',
        'widget_data' => json_encode($widgetData),
      ],
    ]);

    $paragraph->save();
    return $paragraph;
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
    // Supprimer les médias créés pendant le test.
    if (isset($this->imageMedia) && $this->imageMedia) {
      $this->imageMedia->delete();
    }
    if (isset($this->footerMedia) && $this->footerMedia) {
      $this->footerMedia->delete();
    }
    if (isset($this->paragraphDF) && $this->paragraphDF) {
      $this->paragraphDF->delete();
    }
    if (isset($this->paragraphWysiwyg) && $this->paragraphWysiwyg) {
      $this->paragraphWysiwyg->delete();
    }
    if (isset($this->paragraphAsset) && $this->paragraphAsset) {
      $this->paragraphAsset->delete();
    }
    // Désinstaller les modules installés pendant le test.
    if (!empty($this->modulesInstalledDuringTest)) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->uninstall($this->modulesInstalledDuringTest);
    }
    parent::tearDown();
  }

}
