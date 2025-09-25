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
    $this->imageMedia = $this->createMediaEntity(__DIR__ . '/imageWysiwygDF.jpg', 'CTA Image');
    $this->footerMedia = $this->createMediaEntity(__DIR__ . '/imageFooterDF.jpg', 'Footer Logo');

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
   * Test the presence of 'embed_entities' in $exportedFields.
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
      $field = $paragraph->get('field_vactory_component');

      // Export the field value using the plugin.
      $exported = $plugin->exportFieldValue($field);

      $exportedFields = json_decode($exported[0]['widget_data'], TRUE);
      dump($exportedFields);

      // Assert 'components' exists.
      $this->assertArrayHasKey('components', $exportedFields, 'Widget data must contain components.');

      // Parcourir chaque composant.
      foreach ($exportedFields['components'] as $componentGroup) {
        foreach ($componentGroup as $component) {
          // Vérifier les champs 'content' - 'left_content' - 'right_content'.
          if (isset($component['content'])) {
            $this->assertArrayHasKey('embed_entities', $component['content'],
              'Content WYSIWYG doit contenir embed_entities.');
          }
          if (isset($component['bigImage'])) {
            foreach ($component['bigImage'] as $img) {
              $this->assertArrayHasKey('_default', $img, 'bigImage doit contenir _default.');
              $this->assertArrayHasKey('_original', $img['_default'], 'bigImage _default doit contenir _original.');
            }
          }
          if (isset($component['smallImage'])) {
            foreach ($component['smallImage'] as $img) {
              $this->assertArrayHasKey('_default', $img, 'smallImage doit contenir _default.');
              $this->assertArrayHasKey('_original', $img['_default'], 'smallImage _default doit contenir _original.');
            }
          }
        }
      }

      // Vérifier les extra_fields contient embed_entities.
      if (isset($exportedFields['extra_field'])) {
        foreach (['left_content', 'right_content'] as $side) {
          if (isset($exportedFields['extra_field'][$side])) {
            $this->assertArrayHasKey('embed_entities', $exportedFields['extra_field'][$side], "$side doit contenir embed_entities.");
          }
        }
      }
    }
    $this->assertTrue(TRUE);
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
   * Create a Paragraph with a WYSIWYG component and an image.
   */
  protected function createWysiwygParagraph(Media $imageMedia): Paragraph {
    // Récupérer le fichier associé au media.
    $file = $imageMedia->field_media_image->entity;
    $uri = $file->getFileUri();
    $path = \Drupal::service('file_system')
      ->realpath($uri);
    $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);

    // Récupérer les dimensions dynamiquement.
    [$width, $height] = getimagesize($path);

    // Construire le HTML pour le content.
    $contentHtml = sprintf(
      '<p><img data-entity-uuid="%s" data-entity-type="file" src="%s" width="%d" height="%d" alt="content Image"></p>',
      $file->uuid(),
      $url,
      $width,
      $height
    );

    $paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => 'vactory_default:59',
        'widget_data' => json_encode([
          'components' => [
            [
              'bigImage' => [
                [
                  '_default' => [
                    '_original' => \Drupal::service('file_url_generator')
                      ->generateAbsoluteString($imageMedia->field_media_image->entity->getFileUri()),
                  ],
                  'file_name' => 'Generated Media for dummy content',
                  'meta' => [
                    'target_id' => $imageMedia->id(),
                    'alt' => 'Big Image',
                    'title' => 'Big Image',
                    'width' => 650,
                    'height' => 650,
                  ],
                ],
              ],
              'smallImage' => [
                [
                  '_default' => [
                    '_original' => \Drupal::service('file_url_generator')
                      ->generateAbsoluteString($imageMedia->field_media_image->entity->getFileUri()),
                  ],
                  'file_name' => 'Small Image',
                  'meta' => [
                    'target_id' => $imageMedia->id(),
                    'alt' => 'Small Image',
                    'title' => 'Small Image',
                    'width' => 650,
                    'height' => 650,
                  ],
                ],
              ],
              'title' => 'Titre du composant',
              'content' => [
                'value' => $contentHtml,
                'format' => 'full_html',
              ],
              'cta' => [
                'title' => 'Lien CTA',
                'url' => '/node/123',
                'attributes' => [
                  'id' => 'cta-unique-id',
                  'class' => '',
                  'target' => '_self',
                  'rel' => '',
                ],
                'is_external' => FALSE,
              ],
            ],
          ],
        ]),
      ],
    ]);

    $paragraph->save();
    return $paragraph;
  }

  /**
   * Create a Paragraph for a footer with WYSIWYG content and logo.
   */
  protected function createFooterParagraph(Media $footerMedia): Paragraph {
    // Récupérer le fichier associé au media.
    $file = $footerMedia->field_media_image->entity;
    $uri = $file->getFileUri();
    $path = \Drupal::service('file_system')
      ->realpath($uri);
    $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);

    // Récupérer les dimensions dynamiquement.
    [$width, $height] = getimagesize($path);

    // Construire le HTML pour le footer.
    $contentFooterHtmlLeft = sprintf(
      '<p><em>this is a wysiwig text italique</em></p><p><img data-entity-uuid="%s" data-entity-type="file" src="%s" width="%d" height="%d" alt="Footer Image"></p>',
      $file->uuid(),
      $url,
      $width,
      $height
    );

    // Construire le HTML pour le footer.
    $contentFooterHtmlRight = '<p><strong>this is a wysiwig text bold</strong></p>';

    $paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => 'vactory_footer:footer-variant5',
        'widget_data' => json_encode([
          'extra_field' => [
            'enableThemeSwitcher' => 1,
            'logo' => [
              [
                '_default' => [
                  '_original' => \Drupal::service('file_url_generator')
                    ->generateAbsoluteString($footerMedia->field_media_image->entity->getFileUri()),
                ],
                'file_name' => 'Footer Logo',
                'meta' => [
                  'target_id' => $footerMedia->id(),
                  'alt' => 'Footer Logo',
                  'title' => 'Footer Logo',
                  'width' => 650,
                  'height' => 650,
                ],
              ],
            ],
            'use_menu' => 'main',
            'copyrights' => '© Test footer 2025',
            'left_content' => [
              'value' => $contentFooterHtmlLeft,
              'format' => 'full_html',
            ],
            'right_content' => [
              'value' => $contentFooterHtmlRight,
              'format' => 'full_html',
            ],
          ],
          'components' => [
            [
              'cta_social' => [
                'title' => 'Facebook',
                'url' => '/node/265',
                'attributes' => [
                  'label' => '',
                  'class' => 'social-link',
                  'id' => 'cta-social-unique-id',
                  'target' => '_self',
                  'rel' => '',
                ],
                'is_external' => FALSE,
              ],
              'icon' => 'facebook',
            ],
          ],
        ]),
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
