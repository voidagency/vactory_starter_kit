<?php

namespace Drupal\vactory_dynamic_field;

use Drupal\Component\Serialization\Json;

/**
 * Pending content manager service.
 */
class PendingContentManager {

  /**
   * Set field content in pending.
   */
  public function getAllPendingContent($filters = []) {
    $query_string = "SELECT * FROM content_progress WHERE pending=1 ";
    $query_params = [];
    if (!empty($filters)) {
      if (isset($filters['language']) && !empty($filters['language'])) {
        $query_string .= "AND langcode=:langcode ";
        $query_params['langcode'] = $filters['language'];
      }
      if (isset($filters['entity_type']) && !empty($filters['entity_type'])) {
        $query_string .= "AND entity_type=:entity_type ";
        $query_params['entity_type'] = $filters['entity_type'];
      }
      if (isset($filters['nid']) && !empty($filters['nid'])) {
        $query_string .= "AND entity_id=:entity_id ";
        $query_params['entity_id'] = $filters['nid'];
      }
    }
    $result = \Drupal::database()->query($query_string, $query_params)
      ->fetchAll();
    if (!empty($result)) {
      return array_map(fn($el) => Json::decode(Json::encode($el)), $result);
    }
    return [];
  }

  /**
   * Set field content in pending.
   */
  public function getPendingContentCount() {
    $query_string = "SELECT count(*) as count FROM content_progress WHERE pending=1";
    $result = \Drupal::database()->query($query_string)
      ->fetchAll();
    if (!empty($result)) {
      $result = reset($result);
      return $result->count;
    }
    return 0;
  }

  /**
   * Set field content in pending.
   */
  public function getResolvedContentCount() {
    $query_string = "SELECT count(*) as count FROM content_progress WHERE pending=0";
    $result = \Drupal::database()->query($query_string)
      ->fetchAll();
    if (!empty($result)) {
      $result = reset($result);
      return $result->count;
    }
    return 0;
  }

}
