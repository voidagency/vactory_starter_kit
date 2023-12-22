<?php

namespace Drupal\vactory_content_sheets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\Cache\Cache;

/**
 * A Content Sheets Controller.
 */
class ContentSheetsController extends ControllerBase {

  /**
  * Returns .
  */
  public function update(Request $request) {

    // Extract data from the request
    $data = json_decode($request->getContent(), TRUE);
    $key = $data['key'] ?? NULL;
    $langcode = $data['langcode'] ?? NULL;
    $content = $data['content'] ?? NULL;

    // Validate the input
    if (empty($key) || empty($langcode)) {
      return new JsonResponse(['message' => 'Missing required fields'], 400);
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
    // Return a response
    return new JsonResponse(['message' => $message], 200);
  }
}
