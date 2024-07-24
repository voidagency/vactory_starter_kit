<?php

namespace Drupal\vactory_content_package\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\block_content\Entity\BlockContent;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Content package import manager service.
 */
class ContentPackageImportManager implements ContentPackageImportManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Batch size.
   */
  const BATCH_SIZE = 15;

  /**
   * Vactory Content Package Service constructor.
   */
  public function __construct(MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Delete all nodes of given content types.
   */
  public function rollback(array $content_types, string $file_to_import = '', $is_block_delete = FALSE) {

    $nodes = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $content_types, 'IN')
      ->execute();
    $nodes = array_values($nodes);

    if (empty($nodes)) {
      return [];
    }

    $chunk = array_chunk($nodes, self::BATCH_SIZE);
    $operations = [];
    $num_operations = 0;
    foreach ($chunk as $ids) {
      $operations[] = [
        [self::class, 'rollbackCallback'],
        [$ids, $file_to_import],
      ];
      $num_operations++;
    }

    if (!empty($operations)) {
      $batch = [
        'title' => 'Process of deleting nodes',
        'operations' => $operations,
      ];
      if (!$is_block_delete) {
        $batch['finished'] = [self::class, 'rollbackFinished'];
      }
      batch_set($batch);
    }
  }

  /**
   * Rollback batch callback.
   */
  public static function rollbackCallback($nids, $file_to_import, &$context) {
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $paragraphStroage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $nodes = $storage->loadMultiple($nids);
    $skipped_nodes = 0;
    foreach ($nodes as $node) {
      $entity_values = $node->toArray();

      // Skip if node_content_package_exclude is checked.
      if ($node->hasField('node_content_package_exclude') && $node->get('node_content_package_exclude')->value == 1) {
        $skipped_nodes++;
        continue;
      }

      $entity_values = array_diff_key($entity_values, array_flip(ContentPackageManagerInterface::UNWANTED_KEYS));
      $fields = $entityFieldManager->getFieldDefinitions('node', $node->bundle());
      foreach ($entity_values as $field_name => &$field_value) {
        $field_definition = $fields[$field_name] ?? NULL;
        if ($field_definition) {
          $field_type = $field_definition->getType();
          $cardinality = $field_definition->getFieldStorageDefinition()
            ->getCardinality();
          $is_multiple = $cardinality > 1 || $cardinality <= -1;
          $field_settings = $field_definition->getSettings();

          if ($field_type === 'entity_reference_revisions' && isset($field_settings['target_type']) && $field_settings['target_type'] === 'paragraph') {
            $field_value = empty($field_value) ? [] : $field_value;
            if (!empty($field_value)) {
              if (!$is_multiple) {
                $target_id = $field_value[0]['target_id'];
                $paragraph = $paragraphStroage->load($target_id);
                if (isset($paragraph)) {
                  $paragraph->delete();
                }
              }
              else {
                $target_ids = array_map(fn ($value) => $value['target_id'], $field_value);
                foreach ($target_ids as $pid) {
                  $paragraph = $paragraphStroage->load($pid);
                  if (isset($paragraph)) {
                    $paragraph->delete();
                  }
                }
              }
            }
            break;
          }
        }
      }
      $node->delete();
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += (count($nodes) - $skipped_nodes);
    $context['results']['file_to_import'] = $file_to_import;
  }

  /**
   * Delete all Blocks of given content types.
   */
  public function rollbackBlock(string $file_to_import = '') {
    $blocks = $this->entityTypeManager->getStorage('block_content')
      ->getQuery('OR')
      ->notExists('block_content_package_exclude')
      ->condition('block_content_package_exclude', 1, '<>')
      ->accessCheck(FALSE)
      ->execute();
    $blocks = array_values($blocks);
    if (empty($blocks)) {
      return [];
    }

    $chunk = array_chunk($blocks, self::BATCH_SIZE);
    $operations = [];
    $num_operations = 0;
    foreach ($chunk as $ids) {
      $operations[] = [
        [self::class, 'rollbackBlocksCallback'],
        [$ids, $file_to_import],
      ];
      $num_operations++;
    }

    if (!empty($operations)) {
      $batch = [
        'title' => 'Process of deleting blocks',
        'operations' => $operations,
        'finished' => [self::class, 'rollbackFinished'],
      ];
      batch_set($batch);
    }
  }

  /**
   * Rollback batch callbackBlocks.
   */
  public static function rollbackBlocksCallback($nids, $file_to_import, &$context) {
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $storage = \Drupal::entityTypeManager()->getStorage('block_content');
    $nodes = $storage->loadMultiple($nids);
    foreach ($nodes as $node) {
      $node->delete();
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += count($nodes);
    $context['results']['file_to_import'] = $file_to_import;
  }

  /**
   * Rollback batch finished.
   */
  public static function rollbackFinished($success, $results, $operations) {
    if ($success) {
      $message = "Deleting finished: {$results['count']} nodes.";
      \Drupal::messenger()->addStatus($message);
      $url = Url::fromRoute('vactory_content_package.importing_exported_nodes')
        ->setRouteParameters([
          'url' => $results['file_to_import'],
        ]);

      $redirect_response = new TrustedRedirectResponse($url->toString(TRUE)
        ->getGeneratedUrl());
      $redirect_response->send();
      return $redirect_response;
    }
  }

  /**
   * Import nodes.
   */
  public function importNodes(string $file_to_import) {
    if (!file_exists($file_to_import)) {
      return;
    }

    $json_contents = file_get_contents($file_to_import);
    $json_datas = json_decode($json_contents, TRUE);

    if (!empty($json_datas) && is_array($json_datas)) {
      foreach ($json_datas as $content_type => $json_data) {
        if ($content_type === 'menus') {
          $this->processMenus($json_data);
        }
        else {
          $chunk = array_chunk($json_data, self::BATCH_SIZE);
          $operations = [];
          $num_operations = 0;

          foreach ($chunk as $nodes) {
            $operations[] = [
              [self::class, 'importingCallback'],
              [$nodes, $content_type, $file_to_import],
            ];
            $num_operations++;
          }

          if (!empty($operations)) {
            $batch = [
              'title' => "Process of importing $content_type",
              'operations' => $operations,
              'finished' => [self::class, 'importingFinished'],
            ];
            batch_set($batch);
          }
        }
      }
    }
  }

  /**
   * Importing batch callback.
   */
  public static function importingCallback($items, $content_type, $file_to_import, &$context) {

    $logger = \Drupal::logger('vactory_content_package');

    foreach ($items as $key => $value) {
      $entity = NULL;

      if (isset($value['original'])) {
        try {
          if ($content_type === 'pages') {
            $entity = Node::create($value['original']);
            $entity->enforceIsNew();
            $entity->save();
          }
          elseif ($content_type === 'blocks') {
            $entity = BlockContent::create($value['original']);
            $entity->save();
          }

          if ($entity && isset($value['translations'])) {
            foreach ($value['translations'] as $lang => $trans) {
              try {
                $entity->addTranslation($lang, $trans)
                  ->save();
              }
              catch (\Exception $exception) {
                $logger->error(t('Enable to attach translation %lang to entity %label, error message %error', [
                  '%lang' => $lang,
                  '%label' => $key,
                  '%error' => $exception->getMessage(),
                ]));
              }
            }
          }
        }
        catch (\Exception $exception) {
          $logger->error(t('Unable to create entity %label, error message %error', [
            '%label' => $key,
            '%error' => $exception->getMessage(),
          ]));
        }
      }
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += count($items);
    $context['results']['file_to_import'] = $file_to_import;
  }

  /**
   * Importing batch finished.
   */
  public static function importingFinished($success, $results, $operations) {
    if ($success) {
      $message = "Importing process finished: {$results['count']} nodes.";
      \Drupal::messenger()->addStatus($message);
      if (file_exists($results['file_to_import'])) {
        unlink($results['file_to_import']);
      }

      $url = Url::fromRoute('vactory_content_package.import');

      $redirect_response = new TrustedRedirectResponse($url->toString(TRUE)
        ->getGeneratedUrl());
      $redirect_response->send();
      return $redirect_response;
    }
  }

  /**
   * Process menus data to create or update menus and their links.
   */
  protected function processMenus(array $menusData) {
    $jsonContent = json_encode($menusData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    \Drupal::logger('vactory_content_package')->debug('Processing menusData: @content', ['@content' => $jsonContent]);

    foreach ($menusData as $menuId => $menuContentWrapper) {
      foreach ($menuContentWrapper as $menuContentArray) {
        foreach ($menuContentArray as $menuContent) {
          $menuName = $menuContent['menu_name'] ?? 'Unknown menu name';
          $menuSystemName = $menuContent['menu_system_name'] ?? 'Unknown system name';

          $linksCount = !empty($menuContent['links']) ? count($menuContent['links']) : 0;
          \Drupal::logger('vactory_content_package')->debug("Processing menu: $menuName ($menuSystemName) with $linksCount links.");

          $this->ensureMenuExists($menuName, $menuSystemName);
          \Drupal::logger('vactory_content_package')->debug("Processing to finished save creted Menu");

          if (!empty($menuContent['links'])) {
            \Drupal::logger('vactory_content_package')->debug("Processing to createOrUpdateMenuLink");
            foreach ($menuContent['links'] as $linkData) {
              \Drupal::logger('vactory_content_package')->debug("Processing call createOrUpdateMenuLink");
              $this->createOrUpdateMenuLink($linkData, $menuSystemName, '');
            }
          }
        }
      }
    }
    $menuCount = count($menusData);
    // $context['results']['file_to_import'] = $file_to_import;
    $message = "Importing process finished: {$menuCount} Menu.";
    \Drupal::messenger()->addStatus($message);

    $url = Url::fromRoute('vactory_content_package.import');

    $redirect_response = new TrustedRedirectResponse($url->toString(TRUE)
      ->getGeneratedUrl());
    $redirect_response->send();
  }

  /**
   * Ensures a menu exists, or creates it.
   */
  protected function ensureMenuExists($menuName, $menuSystemName) {
    if (empty($menuSystemName)) {
      throw new \Exception("Menu system name is required.");
    }

    $menu_storage = \Drupal::entityTypeManager()->getStorage('menu');
    $menu = $menu_storage->load($menuSystemName);
    if (!$menu) {
      \Drupal::logger('vactory_content_package')->debug("Processing to crete Menu");
      $menu = $menu_storage->create([
        'id' => $menuSystemName,
        'label' => $menuName,
      ]);
      \Drupal::logger('vactory_content_package')->debug("Processing to save creted Menu");

      $menu->save();
    }
  }

  /**
   * Creates or updates a menu link.
   */
  protected function createOrUpdateMenuLink(array $linkData, $menuId, $parentId) {
    \Drupal::logger('vactory_content_package')->debug("Processing create Menu Link");

    // Determine if the URL is external or internal and format it correctly.
    $uri = $linkData['url'];
    if (!preg_match('/^http(s)?:\/\//', $uri)) {
      // Prepend 'internal:' scheme for internal paths.
      $uri = 'internal:' . $uri;
    }

    $dynamicFieldData = [
      "widget_id" => "vactory_default:23",
      "widget_data" => json_encode([
        "0" => [
          "chiffre" => "2",
          "titre" => "Test oF TITLE",
          "description" => "Descr faa",
          "cta" => [
            "title" => "Live Market",
            "url" => "/mode/1",
            "attributes" => [
              "label" => "",
              "class" => "",
              "id" => "cta-mde3mdk4mzc4mju",
              "target" => "_self",
              "rel" => "",
            ],
          ],
          "_weight" => "1",
        ],
        "pending_content" => [],
      ]),
    ];
    $link = MenuLinkContent::create([
      'title' => $linkData['title'],
      'link' => ['uri' => $uri],
      'menu_name' => $menuId,
      'parent' => $parentId ? 'menu_link_content:' . $parentId : '',
      'field_custom_cv' => 'fahd bouaicha',
    ]);

    \Drupal::logger('vactory_content_package')->debug("Processing save created Menu Link");

    $link->save();
    \Drupal::logger('vactory_content_package')->debug("Processing Link Handle translations if present");

    // Handle translations if present.
    if (!empty($linkData['translations'])) {
      \Drupal::logger('vactory_content_package')->debug("Processing Handle translations if present");

      foreach ($linkData['translations'] as $langcode => $translationData) {
        \Drupal::logger('vactory_content_package')->debug("Processing call addMenuLinkTranslation");
        $this->addMenuLinkTranslation($link, $translationData, $langcode);
      }
    }

    // Recursively create or update child links.
    if (!empty($linkData['children'])) {
      \Drupal::logger('vactory_content_package')->debug("Processing Recursively create or update child links");

      foreach ($linkData['children'] as $childLinkData) {
        \Drupal::logger('vactory_content_package')->debug("1 Processing call " . $link->id() . " Recursively create or update child links");
        \Drupal::logger('vactory_content_package')->debug("2 Processing call " . $link->uuid() . " Recursively create or update child links");

        $this->createOrUpdateMenuLink($childLinkData, $menuId, $link->uuid());
      }
    }
  }

  /**
   * Adds a translation to a menu link.
   */
  protected function addMenuLinkTranslation($link, $translationData, $langcode) {
    \Drupal::logger('vactory_content_package')->debug("Processing inter addMenuLinkTranslation");

    if (!$link->hasTranslation($langcode)) {
      \Drupal::logger('vactory_content_package')->debug("Processing inside linke addMenuLinkTranslation");

      $translation = $link->addTranslation($langcode, [
        'title' => $translationData['title'],
        // Add other fields as needed.
      ]);
      \Drupal::logger('vactory_content_package')->debug("Processing save addMenuLinkTranslation");

      $translation->save();
    }
  }

}
