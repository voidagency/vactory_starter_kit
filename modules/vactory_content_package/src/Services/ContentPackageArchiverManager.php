<?php

namespace Drupal\vactory_content_package\Services;

use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\vactory_content_package\ContentPackageConstants;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * Content package archiver manager service.
 */
class ContentPackageArchiverManager implements ContentPackageArchiverManagerInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The archiver manager.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Vactory Content Package Service constructor.
   */
  public function __construct(FileSystemInterface $fileSystem, ArchiverManager $archiverManager, MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->fileSystem = $fileSystem;
    $this->archiverManager = $archiverManager;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Zip nodes and blocks.
   */
  public function zipContentTypeNodes(string $contentType = 'vactory_page', $nodes = NULL, $blocks = NULL, $menus = NULL, $is_partial = FALSE) {
    if (empty($nodes) && !$is_partial) {
      $nodes = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $contentType)
        ->execute();
      $nodes = array_values($nodes);
    }

    // Get blocks.
    if (empty($blocks) && !$is_partial) {
      $blocks = $this->entityTypeManager->getStorage('block_content')
        ->getQuery('OR')
        ->notExists('block_content_package_exclude')
        ->condition('block_content_package_exclude', 1, '<>')
        ->accessCheck(FALSE)
        ->execute();
      $blocks = array_values($blocks);
    }

    if (empty($nodes) && empty($blocks) && empty($menus)) {
      return NULL;
    }

    $destination = $this->extractDirectory();

    $archivePath = ContentPackageConstants::EXPORT_DES_FILES . '/' . ContentPackageConstants::ARCHIVE_FILE_NAME;

    $this->fileSystem->prepareDirectory($archivePath, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $chunk = array_chunk($nodes ?? [], self::BATCH_SIZE);
    $operations = [];
    $num_operations = 0;
    foreach ($chunk as $ids) {
      $operations[] = [
        [self::class, 'zipCallback'],
        [$ids, $destination, 'node'],
      ];
      $num_operations++;
    }

    $chunk = array_chunk($blocks ?? [], self::BATCH_SIZE);
    foreach ($chunk as $ids) {
      $operations[] = [
        [self::class, 'zipCallback'],
        [$ids, $destination, 'block'],
      ];
      $num_operations++;
    }

    // Add menu operations.
    if (!empty($menus)) {
      foreach ($menus as $menu_name) {
        // Wrap menu_name in an array since zipCallback.
        // Expects the first argument as an array.
        $operations[] = [
          [self::class, 'zipCallback'],
          [[$menu_name], $destination, 'menu'],
        ];
      }
    }

    if (!empty($operations)) {
      $batch = [
        'title' => 'Process of zipping',
        'operations' => $operations,
        'finished' => [self::class, 'zipFinished'],
      ];
      batch_set($batch);
    }
  }

  /**
   * Zip batch callback.
   */
  public static function zipCallback($ids, $destination, $entity_type, &$context) {
    // Access the EntityFieldManager service directly within the method.
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fileSystem = \Drupal::service('file_system');
    $langs = array_keys(\Drupal::service('language_manager')->getLanguages());
    $entityRepository = \Drupal::service('entity.repository');
    $contentPackageManager = \Drupal::service('vactory_content_package.manager');
    $zip_file_path = ContentPackageConstants::EXPORT_DES_FILES . '/' . ContentPackageConstants::ARCHIVE_FILE_NAME . '.zip';

    if (!isset($context['sandbox']['zip'])) {
      $context['sandbox']['zip'] = new \ZipArchive();
      if ($context['sandbox']['zip']->open($fileSystem->realPath($zip_file_path), \ZipArchive::CREATE) !== TRUE) {
        \Drupal::logger('vactory_content_package')->debug('Cannot open archive file');
        return;
      }
    }

    // Handling for menus.
    if ($entity_type === 'menu') {
      foreach ($ids as $menu_name) {
        // Load the menu tree.
        $menu_tree = \Drupal::menuTree();
        $parameters = new MenuTreeParameters();
        $parameters->maxDepth = 10;
        $tree = $menu_tree->load($menu_name, $parameters);
        $manipulators = [
          ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
          ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ];
        $tree = $menu_tree->transform($tree, $manipulators);

        $menu_entity = \Drupal::entityTypeManager()->getStorage('menu')->load($menu_name);

        if ($menu_entity) {
          /*
           * Convert the menu entity to an array and then
           * to a JSON string for logging.
           */
          $menu_entity_array = $menu_entity->toArray();
          $menu_entity_json = json_encode($menu_entity_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
          \Drupal::logger('vactory_content_package')->debug("Menu entity data: @data", ['@data' => $menu_entity_json]);
        }

        // Check if the menu entity is loaded.
        if ($menu_entity) {

          // Access additional properties from the menu entity.
          // Gets the human-readable title of the menu.
          $menu_title = $menu_entity->label();

          // Get the menu description.
          $menu_description = $menu_entity->getDescription();

          // Check if the menu is locked.
          $menu_locked = $menu_entity->isLocked();

          // Access status and langcode using the appropriate methods.
          $menu_status = $menu_entity->get('status')->value;
          $menu_langcode = $menu_entity->language()->getId();
          \Drupal::logger('vactory_content_package')->debug("Menu langcode: @langcode", ['@langcode' => $menu_entity->language()->getId()]);

          // Convert the menu entity to an array.
          $menu_entity_array = $menu_entity->toArray();

          // Convert the array to a JSON string.
          $menu_entity_json = json_encode($menu_entity_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

          // Log the entire content of the menu entity.
          \Drupal::logger('vactory_content_package')->debug("Menu entity content: @menu_content", ['@menu_content' => $menu_entity_json]);
        }
        else {
          \Drupal::logger('vactory_content_package')->debug("Failed to load menu entity: @menu_name", ['@menu_name' => $menu_name]);
        }

        /*
         * Get the list of languages as an associative array
         * with language codes as keys.
         */
        $languages = \Drupal::languageManager()->getLanguages();
        $langcodes = array_keys($languages);

        $menu_data = [
          'menu_name' => $menu_title,
          'menu_system_name' => $menu_name,
          'description' => $menu_description,
          'locked' => $menu_locked,
          'status' => $menu_status,
          'langcode' => $menu_langcode,
          'links' => [],
        ];

        /*
         * Pass the menu name, the full languages array,
         * and NULL for parent_id to the extractLinksInfo function.
         */

        /*
         * This initial call is for top-level menu items,
         * so they don't have a parent.
         */
        // $entityFieldManager = $this->entityFieldManager;.
        self::extractLinksInfo($tree, $menu_data['links'], $menu_name, $languages, NULL, $entityFieldManager);

        $dir_location = $destination . '/menus/' . $menu_name;
        $fileSystem->prepareDirectory($dir_location, FileSystemInterface::CREATE_DIRECTORY);
        $json_file_name = $menu_name . '.json';
        file_put_contents($dir_location . '/' . $json_file_name, json_encode($menu_data, JSON_PRETTY_PRINT));
        $context['sandbox']['zip']->addFile($fileSystem->realPath($dir_location . '/' . $json_file_name), 'menus/' . $menu_name . '/' . $json_file_name);
      }
    }
    else {
      $skipped_entities = 0;
      foreach ($ids as $id) {
        // Get original entity.
        $entity = NULL;
        if ($entity_type === 'node') {
          $entity = Node::load($id);
        }
        elseif ($entity_type === 'block') {
          $entity = BlockContent::load($id);
        }

        if ($entity === NULL) {
          continue;
        }

        // Skip if entity_content_package_exclude is checked.
        $exclude_field_name = $entity_type . '_content_package_exclude';
        if ($entity->hasField($exclude_field_name) && $entity->get($exclude_field_name)->value == 1) {
          $skipped_entities++;
          continue;
        }

        // Create subdirectory for nodes or blocks.
        $entity_folder = $entity_type === 'node' ? 'pages' : 'blocks';
        $dir_location = $destination . '/' . $entity_folder . '/' . $entity->label();

        // Create subdirectory within the main folder.
        $fileSystem->prepareDirectory($dir_location, FileSystemInterface::CREATE_DIRECTORY);
        $json_file_name = 'original.json';
        file_put_contents($dir_location . '/' . $json_file_name, json_encode($contentPackageManager->normalize($entity), JSON_PRETTY_PRINT));
        $context['sandbox']['zip']->addFile($fileSystem->realpath($dir_location . '/' . $json_file_name), $entity_folder . '/' . $entity->label() . '/' . $json_file_name);
        // Get translations.
        foreach ($langs as $lang) {
          if ($lang == $entity->language()->getId()) {
            continue;
          }

          if ($entity->hasTranslation($lang)) {
            $translated_entity = $entityRepository->getTranslationFromContext($entity, $lang);

            if ($translated_entity !== NULL) {
              $lang_json_file_name = $lang . '.json';
              file_put_contents($dir_location . '/' . $lang_json_file_name, json_encode($contentPackageManager->normalize($translated_entity, TRUE), JSON_PRETTY_PRINT));
              $context['sandbox']['zip']->addFile($fileSystem->realpath($dir_location . '/' . $lang_json_file_name), $entity_folder . '/' . $entity->label() . '/' . $lang_json_file_name);
            }
          }
        }
      }
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    // Update count based on entity type; menus are counted differently.
    $context['results']['count'] += ($entity_type === 'menu') ? count($ids) : (count($ids) - $skipped_entities);
  }

  /**
   * Zip batch finished.
   */
  public static function zipFinished($success, $results, $operations) {
    if ($success) {
      $message = $results['count'] > 0 ? "Zipping finished: {$results['count']} entities." : "Oops, Nothing to export";
      \Drupal::messenger()->addStatus($message);
      if ($results['count'] > 0) {
        foreach ($results as $result) {
          if (isset($result['zip']) && $result['zip'] instanceof \ZipArchive) {
            $result['zip']->close();
          }
        }
        $redirect_response = new TrustedRedirectResponse(Url::fromRoute('vactory_content_package.download')
          ->toString(TRUE)->getGeneratedUrl());
        $redirect_response->send();
        return $redirect_response;
      }
    }
  }

  /**
   * ExtractLinksInfo for Menu.
   */
  public static function extractLinksInfo($tree, &$links_info, $menu_name = '', $languages = [], $parent_id = NULL, EntityFieldManagerInterface $entityFieldManager = NULL) {
    $contentPackageManager = \Drupal::service('vactory_content_package.manager');

    foreach ($tree as $element) {
      $link_info = [];
      /*
       * Check if the link property of the element
       * is a MenuLinkContent plugin.
       */
      if ($element->link instanceof MenuLinkContent) {
        $entity = $element->link->getEntity();
        $entity_type = $entity->getEntityTypeId();
        $bundle = $entity->bundle();
        // Convert the entity to an array for logging.
        $entity_array = $entity->toArray();
        $entity_array = array_diff_key($entity_array, array_flip([]));
        $fields = $entityFieldManager->getFieldDefinitions($entity_type, $bundle);
        \Drupal::logger('vactory_content_package_extractLinksInfo-getFieldDefinitions')->debug(sprintf("getFieldDefinitions entity_type: %s bundle: %s", $entity_type, $bundle));

        foreach ($entity_array as $field_name => &$field_value) {
          $field_definition = $fields[$field_name] ?? NULL;
          if ($field_definition) {
            $field_type = $field_definition->getType();
            if ($field_type === 'field_wysiwyg_dynamic' && !empty($field_value)) {
              $link_info[$field_name] = $contentPackageManager->normalizeFieldWysiwygDynamic($field_value, $entity_array);
              \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("2 Test Value: %s", json_encode($field_value)));
              $field_value_modified = json_encode($contentPackageManager->normalizeFieldWysiwygDynamic($field_value, $entity_array), JSON_PRETTY_PRINT);
              $field_value[$field_name] = json_encode($contentPackageManager->normalizeFieldWysiwygDynamic($field_value, $entity_array), JSON_PRETTY_PRINT);
              \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("Result Test Value: %s", json_encode($field_value)));
              \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("final Result Test Value: %s", json_encode($field_value_modified)));

              if (is_string($field_value_modified)) {
                $field_value_modified = json_decode($field_value_modified, TRUE);
              }

              $modified_field_value_modified = [
                $field_name => $field_value_modified,
              ];

              $field_value = $modified_field_value_modified;

              $field_value = json_encode($modified_field_value_modified, JSON_PRETTY_PRINT);
              \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("final 2 Result Test  Value: %s", json_encode($field_value)));
              \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("final 2.2 Result Test  Value: %s", json_encode($field_value)));
            }
          }
        }

        $entity_json = json_encode($entity_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($entity_json === FALSE) {
          $log_message = "Failed to encode menu link content entity to JSON.";
          $context = ['error' => json_last_error_msg()];
        }
        else {
          $log_message = "Menu link content entity: @entity";
          $context = ['@entity' => $entity_json];
        }

        \Drupal::logger('vactory_content_package')->debug($log_message, $context);
      }
      else {
        // If the link is not a MenuLinkContent, log its type.
        $linkType = is_object($element->link) ? get_class($element->link) : gettype($element->link);
        \Drupal::logger('vactory_content_package')->debug("Link is not a MenuLinkContent, it is of type: @type", ['@type' => $linkType]);
      }

      // Attempt to convert the element to a JSON string for logging.
      $element_json = json_encode($element, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

      if ($element_json === FALSE) {
        // json_encode failed, use a placeholder message.
        $log_message = "Failed to encode menu element to JSON.";
        $context = ['error' => json_last_error_msg()];
      }
      else {
        // json_encode succeeded, log the actual content.
        $log_message = "Menu entity element content: @element";
        $context = ['@element' => $element_json];
      }

      \Drupal::logger('vactory_content_package')->debug($log_message, $context);

      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      $title = $link->getTitle();
      $url = $link->getUrlObject()->toString();
      $hasChildren = !empty($element->subtree);

      // Determine the parent ID.
      // This could be NULL for top-level items.
      $parentLinkId = $parent_id;

      // Fetch the menu link content entity to access lang and translations.
      $menu_link_content = NULL;
      if ($link_plugin = $link->getPluginDefinition()) {
        if (isset($link_plugin['metadata']['entity_id'])) {
          $menu_link_content = \Drupal::entityTypeManager()
            ->getStorage('menu_link_content')
            ->load($link_plugin['metadata']['entity_id']);
          // Assuming we're using the entity ID as the unique identifier.
          $current_link_id = $menu_link_content->uuid();
        }
      }

      // Looping on initial data to extract the dynamic key and its content.
      foreach ($field_value as $key => $value) {
        // Using the dynamic key as the new key and assigning its content.
        $newStructure[$key] = $value;
        $key_field = $key;
      }

      // Initialize link_info array with additional details from the entity.
      $link_info = [
        'id' => $menu_link_content ? $menu_link_content->id() : NULL,
        'uuid' => $menu_link_content ? $menu_link_content->uuid() : NULL,
        'title' => $title,
        'url' => $url,
        'hasChildren' => $hasChildren,
        'menu_name' => $menu_name,
        'translations' => [],
        'children' => [],
        'parent' => $parentLinkId,
        'enabled' => $menu_link_content ? $menu_link_content->isEnabled() : NULL,
        'description' => $menu_link_content ? $menu_link_content->getDescription() : '',
        // Determine if the link is external based on the URI.
        'external' => $menu_link_content && strpos($menu_link_content->get('link')->uri, 'http://') === 0 || strpos($menu_link_content->get('link')->uri, 'https://') === 0,
      ];
      // Decode $field_value if it's a JSON string.
      if (is_string($field_value)) {
        $field_value = json_decode($field_value, TRUE);
        \Drupal::logger('your_module')->notice('Decoded $field_value: ' . json_encode($field_value));
      }

      if (!empty($field_value)) {
        // Now log the structure to ensure it's correct for iteration.
        \Drupal::logger('your_module')->notice('Ready to process $field_value: ' . json_encode($field_value));

        foreach ($field_value as $dynamicKey => $dynamicValue) {
          \Drupal::logger('your_module')->notice('Processing dynamicKey: ' . $dynamicKey . ' with dynamicValue: ' . json_encode($dynamicValue));
          $link_info[$dynamicKey] = $dynamicValue;
        }
      }
      else {
        \Drupal::logger('your_module')->notice('$field_value is empty or not an array.');
      }

      // If we have a menu link content entity, get the translations.
      if ($menu_link_content) {
        $current_langcode = $menu_link_content->language()->getId();
        foreach ($languages as $langcode => $language) {
          if ($langcode !== $current_langcode && $menu_link_content->hasTranslation($langcode)) {
            $translated_link_content = $menu_link_content->getTranslation($langcode);
            $translated_title = $translated_link_content->getTitle();
            $translated_url = $translated_link_content->getUrlObject()->toString();

            // Additional information you want to include for translations.
            $translated_enabled = $translated_link_content->isEnabled();
            $translated_description = $translated_link_content->getDescription();

            // Add the translation details to the link_info array.
            $link_info['translations'][$langcode] = [
              'title' => $translated_title,
              'url' => $translated_url,
              'enabled' => $translated_enabled,
              'description' => $translated_description,
            ];
          }
        }
      }

      if ($hasChildren) {
        self::extractLinksInfo($element->subtree, $link_info['children'], $menu_name, $languages, $current_link_id, $entityFieldManager);
      }

      $links_info[] = $link_info;
    }
  }

  /**
   * Unzip a file.
   */
  public function unzipFile(string $path) {
    $directory = $this->extractDirectory();
    try {
      $archive = $this->archiveExtract($path, $directory);
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
      return;
    }

    $files = $archive->listContents();
    if (!$files) {
      return [];
    }

    $path = $this->fileSystem->realpath($directory);
    $subdirectories = glob($path . '/*', GLOB_ONLYDIR);

    if (!empty($subdirectories)) {
      $operations = [];
      foreach ($subdirectories as $folder) {
        $type = '';
        if (str_ends_with($folder, '/blocks')) {
          $type = 'blocks';
        }
        if (str_ends_with($folder, '/pages')) {
          $type = 'pages';
        }
        if (str_ends_with($folder, '/menus')) {
          $type = 'menus';
        }
        $nodes = glob($folder . '/*', GLOB_ONLYDIR);
        $chunk = array_chunk($nodes, self::BATCH_SIZE);

        foreach ($chunk as $nodesChunk) {
          $operations[] = [
            [self::class, 'unzipCallback'],
            [$nodesChunk, $type],
          ];
        }
      }

      if (!empty($operations)) {
        $batch = [
          'title' => 'Process of unzipping',
          'operations' => $operations,
          'finished' => [self::class, 'unzipFinished'],
        ];
        batch_set($batch);
      }
    }
  }

  /**
   * Unzip batch callback.
   */
  public static function unzipCallback($nodes, $type, &$context) {
    \Drupal::logger('vactory_content_package')->debug('Processing type: @type', ['@type' => $type]);

    $fileSystem = \Drupal::service('file_system');
    $contentPackageManager = \Drupal::service('vactory_content_package.manager');

    if (!isset($context['results']['export_data_file'])) {
      $fileLoc = $fileSystem->realPath(ContentPackageConstants::EXPORT_DES_FILES . '-' . self::uniqueIdentifier() . '.json');
      if (file_exists($fileLoc)) {
        unlink($fileLoc);
      }
      $context['results']['export_data_file'] = $fileLoc;
    }

    foreach ($nodes as $subdirectory) {
      $json_files = $fileSystem->scanDirectory($subdirectory, '/\.json/i');
      if (empty($json_files)) {
        continue;
      }
      $directory_name = basename($subdirectory);
      foreach ($json_files as $filePath => $fileInfo) {
        if (!is_dir($filePath)) {
          $json_contents = file_get_contents($filePath);
          $json_data = json_decode($json_contents, TRUE);
          // Log the content of each JSON file.
          \Drupal::logger('vactory_content_package')
            ->debug('Content of JSON file (@type): @content', [
              '@type' => $type,
              '@content' => $json_contents,
            ]);

          if ($type === 'menus') {
            // Use denormalizeMenu for menu data.
            $context['results']['data'][$type][$directory_name][$fileInfo->name] = $contentPackageManager->denormalizeMenu($json_data);
          }
          else {
            // Existing logic for blocks and pages using denormalize.
            if ($fileInfo->name !== 'original') {
              $context['results']['data'][$type][$directory_name]['translations'][$fileInfo->name] = $contentPackageManager->denormalize($json_data);
              continue;
            }
            $context['results']['data'][$type][$directory_name][$fileInfo->name] = $contentPackageManager->denormalize($json_data);
          }
        }
      }

      $existing_json_data = '';
      if (file_exists($context['results']['export_data_file'])) {
        $existing_json_data = file_get_contents($context['results']['export_data_file']);
      }

      $existing_array = json_decode($existing_json_data, TRUE);
      $existing_array[$type][$directory_name] = $context['results']['data'][$type][$directory_name];
      $updated_json_data = json_encode($existing_array, JSON_PRETTY_PRINT);
      $fileSystem->saveData($updated_json_data, $context['results']['export_data_file'], FileSystemInterface::EXISTS_REPLACE);
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += count($nodes);
    \Drupal::logger('vactory_content_package')->debug('Context: @context', ['@context' => print_r($context, TRUE)]);
  }

  /**
   * Unzip batch finished.
   */
  public static function unzipFinished($success, $results, $operations) {
    if ($success) {
      $message = "Unzipping finished: {$results['count']} nodes.";
      \Drupal::messenger()->addStatus($message);
      $url = Url::fromRoute('vactory_content_package.confirm')
        ->setRouteParameters([
          'url' => $results['export_data_file'],
        ]);

      $redirect_response = new TrustedRedirectResponse($url->toString(TRUE)
        ->getGeneratedUrl());
      $redirect_response->send();
      return $redirect_response;
    }
  }

  /**
   * Unpacks a downloaded archive file.
   */
  private function archiveExtract($file, $directory) {
    $archiver = $this->archiverManager->getInstance(['filepath' => $file]);
    if (!$archiver) {
      throw new \Exception($this->t('Cannot extract %file, not a valid archive.', ['%file' => $file]));
    }

    if (file_exists($directory)) {
      $this->fileSystem->deleteRecursive($directory);
    }

    $archiver->extract($directory);
    return $archiver;
  }

  /**
   * Gets the directory where zip files should be extracted.
   */
  private function extractDirectory($create = TRUE) {
    $directory = &drupal_static(__FUNCTION__, '');
    if (empty($directory)) {
      $directory = ContentPackageConstants::FILES_EXTRACTED_DES_PREFIX_DIR . '-' . self::uniqueIdentifier();
      if ($create && !file_exists($directory)) {
        mkdir($directory);
      }
    }
    return $directory;
  }

  /**
   * Returns a short unique identifier.
   */
  private static function uniqueIdentifier() {
    $id = &drupal_static(__FUNCTION__, '');
    if (empty($id)) {
      return substr(hash('sha256', Settings::getHashSalt()), 0, 8);
    }
    return $id;
  }

}
