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
  public function __construct(FileSystemInterface $fileSystem, ArchiverManager $archiverManager, MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->fileSystem = $fileSystem;
    $this->archiverManager = $archiverManager;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Zip nodes and blocks.
   */
  public function zipContentTypeNodes(string $contentType = 'vactory_page', $nodes = NULL, $blocks = NULL, $is_partial = false) {
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
        ->getQuery()
        ->accessCheck(FALSE)
        ->execute();
      $blocks = array_values($blocks);
    }

    if (empty($nodes) && empty($blocks)) {
      return NULL;
    }

    $destination = $this->extractDirectory();

    $archivePath = ContentPackageConstants::EXPORT_DES_FILES . '/' . ContentPackageConstants::ARCHIVE_FILE_NAME;

    $this->fileSystem->prepareDirectory($archivePath, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $chunk = array_chunk($nodes, self::BATCH_SIZE);
    $operations = [];
    $num_operations = 0;
    foreach ($chunk as $ids) {
      $operations[] = [
        [self::class, 'zipCallback'],
        [$ids, $destination, 'node'],
      ];
      $num_operations++;
    }

    $chunk = array_chunk($blocks, self::BATCH_SIZE);
    foreach ($chunk as $ids) {
      $operations[] = [
        [self::class, 'zipCallback'],
        [$ids, $destination, 'block'],
      ];
      $num_operations++;
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
    $fileSystem = \Drupal::service('file_system');
    $langs = array_keys(\Drupal::service('language_manager')->getLanguages());
    $entityRepository = \Drupal::service('entity.repository');
    $contentPackageManager = \Drupal::service('vactory_content_package.manager');
    $zip_file_path = ContentPackageConstants::EXPORT_DES_FILES . '/' . ContentPackageConstants::ARCHIVE_FILE_NAME . '.zip';
    if (!isset($context['sandbox']['zip'])) {
      $context['sandbox']['zip'] = new \ZipArchive();
      $context['sandbox']['zip']->open($fileSystem->realPath($zip_file_path), \ZipArchive::CREATE);
    }
    $skipped_entities = 0;
    foreach ($ids as $id) {
      // Get original entity.
      $entity = NULL;
      if ($entity_type === 'node') {
        $entity = Node::load($id);
      } elseif ($entity_type === 'block') {
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
      // $json_file_name = $entity_type === 'node' ? 'original.json' : 'block_original.json';
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
            $lang_json_file_name =  $lang . '.json';
            file_put_contents($dir_location . '/' . $lang_json_file_name, json_encode($contentPackageManager->normalize($translated_entity, TRUE), JSON_PRETTY_PRINT));
            $context['sandbox']['zip']->addFile($fileSystem->realpath($dir_location . '/' . $lang_json_file_name), $entity_folder . '/' . $entity->label() . '/' . $lang_json_file_name);
          }
        }
      }
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += (count($ids) - $skipped_entities);
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
   * Unzip a file.
   */
  public function unzipFile(string $path) {
    $directory = $this->extractDirectory();
    try {
      $archive = $this->archiveExtract($path, $directory);
    }catch (\Exception $e) {
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
    // \Drupal::logger('your_module')->debug('Context befor unlink: @context', [
    //   '@context' => print_r($context, TRUE),
    // ]);
  
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
          if ($fileInfo->name !== 'original') {
            $context['results']['data'][$type][$directory_name]['translations'][$fileInfo->name] = $contentPackageManager->denormalize($json_data);
            continue;
          }
          $context['results']['data'][$type][$directory_name][$fileInfo->name] = $contentPackageManager->denormalize($json_data);
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
