<?php

namespace Drupal\vactory_content_sheets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\Cache\Cache;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileSystemInterface;

/**
 * A Content Sheets Controller.
 */
class ContentSheetsController extends ControllerBase {

  /**
   * Updates content.
   */
  public function update(Request $request) {
    // Extract data from the request
    $data = json_decode($request->getContent(), TRUE);
    $key = $data['key'] ?? NULL;
    $langcode = $data['langcode'] ?? NULL;
    $content = $data['content'] ?? NULL;

    // Validate the input
    if (empty($key) || empty($langcode)) {
      return new JsonResponse(['error' => 'Missing required fields'], 400);
    }

    // Connect to the database
    $connection = Database::getConnection();
    $table = 'vactory_content_sheets';

    // Check if the entry exists
    $query = $connection->select($table, 'v')
      ->fields('v')
      ->condition('key', $key)
      ->condition('langcode', $langcode)
      ->execute();

    $content = $this->getContent($key, $content);

    if ($query->fetchAssoc()) {
      // Update existing entry
      $connection->update($table)
        ->fields(['content' => $content])
        ->condition('key', $key)
        ->condition('langcode', $langcode)
        ->execute();
      $message = 'Content updated successfully';
    } else {
      // Insert new entry
      $connection->insert($table)
        ->fields([
          'key' => $key,
          'langcode' => $langcode,
          'content' => $content,
        ])
        ->execute();
      $message = 'Content added successfully';
    }

    Cache::invalidateTags([$key]);
    Cache::invalidateTags(['node_list:vactory_page']);
    Cache::invalidateTags(['block_content_list']);
    Cache::invalidateTags(['block_list']);

    clear_next_cache();
    return new JsonResponse(['message' => $message], 200);
  }

  /**
   * Get content.
   */
  protected function getContent($key, $content) {
    if (str_starts_with($key, 'img:')) {
      $file_id = $this->createFile($content);
      if ($file_id !== NULL) {
        return $this->createMedia('image', 'field_media_image', ['target_id' => $file_id]);
      } else {
        throw new \RuntimeException('Failed to create media. Invalid file content.');
      }
    }

    if (str_starts_with($key, 'ytb:')) {
      return $this->createMedia('remote_video', 'field_media_oembed_video', $content);
    }

    return $content;
  }

  /**
   * Create file entity.
   */
  protected function createFile($file_path) {
    $source_file_content = file_get_contents($file_path);

    if ($source_file_content !== FALSE) {
      $file_name = basename($file_path);
      if (!file_exists('public://content-media')) {
        mkdir('public://content-media', 0775, TRUE);
      }

      try {
        $file_repository = \Drupal::service('file.repository');
        $file = $file_repository->writeData($source_file_content, "public://content-media/" . $file_name, FileSystemInterface::EXISTS_REPLACE);
        return $file->id();
      } catch (\Exception $e) {
        throw new FileWriteException($e->getMessage());
      }
    }

    return NULL;
  }

  /**
   * Create media entity.
   */
  protected function createMedia($bundle, $media_field_name, $media_field_value = NULL) {
    $media_values = [
      'bundle' => $bundle,
      'uid' => \Drupal::currentUser()->id(),
      $media_field_name => $media_field_value,
    ];

    try {
      $media = Media::create($media_values);
      $media->save();
      return $media->id();
    } catch (\Exception $e) {
      throw new \RuntimeException('Failed to create media: ' . $e->getMessage());
    }
  }
}