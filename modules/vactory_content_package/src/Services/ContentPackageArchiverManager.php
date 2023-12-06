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
   * Zip nodes.
   */
  public function zipContentTypeNodes(string $contentType = 'vactory_page', $nodes = NULL) {
    if (empty($nodes)) {
      $nodes = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $contentType)
        ->execute();
      $nodes = array_values($nodes);
    }

    if (empty($nodes)) {
      return NULL;
    }

    $destination = $this->extractDirectory();

    $archivePath = ContentPackageConstants::EXPORT_DES_FILES . '/' . ContentPackageConstants::ARCHIVE_FILE_NAME;

    $this->fileSystem->prepareDirectory($archivePath, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $chunk = array_chunk($nodes, self::BATCH_SIZE);
    $operations = [];
    $num_operations = 0;
    foreach ($chunk as $ids) {
      $operations[] = [
        [self::class, 'zipCallback'],
        [$ids, $destination],
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
  public static function zipCallback($ids, $destination, &$context) {
    $fileSystem = \Drupal::service('file_system');
    $langs = array_keys(\Drupal::service('language_manager')->getLanguages());
    $entityRepository = \Drupal::service('entity.repository');
    $contentPackageManager = \Drupal::service('vactory_content_package.manager');
    $zip_file_path = ContentPackageConstants::EXPORT_DES_FILES . '/' . ContentPackageConstants::ARCHIVE_FILE_NAME . '.zip';
    if (!isset($context['sandbox']['zip'])) {
      $context['sandbox']['zip'] = new \ZipArchive();
      $context['sandbox']['zip']->open($fileSystem->realPath($zip_file_path), constant('ZipArchive::CREATE'));
    }
    $skipped_nodes = 0;
    foreach ($ids as $nid) {
      // Get original node.
      $node = Node::load($nid);
      // Skip if node_content_package_exclude is checked.
      if ($node->hasField('node_content_package_exclude') && $node->get('node_content_package_exclude')->value == 1) {
        $skipped_nodes++;
        continue;
      }
      $dir_location = $destination . '/' . $node->label();
      $fileSystem->prepareDirectory($dir_location, FileSystemInterface::CREATE_DIRECTORY);
      file_put_contents($dir_location . '/original.json', json_encode($contentPackageManager->normalize($node), JSON_PRETTY_PRINT));
      $context['sandbox']['zip']->addFile($fileSystem->realpath($dir_location . '/original.json'), $node->label() . '/original.json');
      // Get translations.
      foreach ($langs as $lang) {
        if ($lang == $node->language()->getId()) {
          continue;
        }
        if ($node->hasTranslation($lang)) {
          $translated_node = $entityRepository->getTranslationFromContext($node, $lang);
          if (isset($translated_node)) {
            file_put_contents($dir_location . '/' . $lang . '.json', json_encode($contentPackageManager->normalize($translated_node, TRUE), JSON_PRETTY_PRINT));
            $context['sandbox']['zip']->addFile($fileSystem->realpath($dir_location . '/' . $lang . '.json'), $node->label() . '/' . $lang . '.json');
          }
        }
      }
    }

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $context['results']['count'] += (count($ids) - $skipped_nodes);
  }

  /**
   * Zip batch finished.
   */
  public static function zipFinished($success, $results, $operations) {
    if ($success) {

      $message = $results['count'] > 0 ? "Zipping finished: {$results['count']} nodes." : "Oops, Nothing to export";
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

    $chunk = array_chunk($subdirectories, self::BATCH_SIZE);

    if (!empty($chunk)) {
      $operations = [];
      $num_operations = 0;
      foreach ($chunk as $nodes) {

        $operations[] = [
          [self::class, 'unzipCallback'],
          [$nodes],
        ];
        $num_operations++;

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
  public static function unzipCallback($nodes, &$context) {
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
            $context['results']['data'][$directory_name]['translations'][$fileInfo->name] = $contentPackageManager->denormalize($json_data);
            continue;
          }
          $context['results']['data'][$directory_name][$fileInfo->name] = $contentPackageManager->denormalize($json_data);
        }
      }

      $existing_json_data = '';
      if (file_exists($context['results']['export_data_file'])) {
        $existing_json_data = file_get_contents($context['results']['export_data_file']);
      }

      $existing_array = json_decode($existing_json_data, TRUE);
      $existing_array[$directory_name] = $context['results']['data'][$directory_name];
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
