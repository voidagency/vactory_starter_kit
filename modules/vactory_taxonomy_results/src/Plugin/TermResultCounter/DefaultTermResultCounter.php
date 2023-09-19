<?php

namespace Drupal\vactory_taxonomy_results\Plugin\TermResultCounter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\vactory_taxonomy_results\Annotation\TermResultCounter;
use Drupal\vactory_taxonomy_results\TermResultCounterManagerBase;

/**
 * @TermResultCounter(
 *   id="default",
 * )
 */
class DefaultTermResultCounter extends TermResultCounterManagerBase {

  /**
   * {@inheritDoc}
   */
  public function termResultCount(EntityInterface $entity, $field_name) {
    $plugin_id = $this->getPluginId();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $status_field = $this->entityTypeManager->getDefinition($entity_type)->getKey('status');
    $status_field = !$status_field ? $this->entityTypeManager->getDefinition($entity_type)->getKey('published') : $status_field;

    // Get original entity.
    $original_entity = NULL;
    if ($entity->original) {
      $original_entity = $entity->original;
    }

    // Get new field taxonomy term reference value(s).
    $tids = $entity->get($field_name)->getValue();
    $tids = array_map(function ($value) {
      return $value['target_id'];
    }, $tids);

    $original_status = NULL;
    $new_status = NULL;
    if (!empty($status_field)) {
      $new_status = $entity->get($status_field)->value;
    }
    $original_tids = [];
    if ($original_entity) {
      // Get old field taxonomy term reference value(s).
      $original_tids = $original_entity->get($field_name)->getValue();
      $original_tids = array_map(function ($value) {
        return $value['target_id'];
      }, $original_tids);

      if (!empty($status_field)) {
        $original_status = $original_entity->get($status_field)->value;
      }

      foreach ($original_tids as $original_tid) {
        if (!in_array($original_tid, $tids)) {
          if (!empty($status_field) && $original_status) {
            // Decrement removed term results counter of an already published content.
            $original_langcode = $original_entity->get('langcode')->value;
            $this->taxonomyResultsHelper->decrementTermResultCount($original_tid, $entity_type, $bundle, $plugin_id, $original_langcode);
          }
          if (empty($status_field)) {
            // Decrement removed term results counter.
            $original_langcode = $original_entity->get('langcode')->value;
            $this->taxonomyResultsHelper->decrementTermResultCount($original_tid, $entity_type, $bundle, $plugin_id, $original_langcode);
          }
        }

        if (!empty($status_field) && !$new_status && $original_status && in_array($original_tid, $tids)) {
          // Decrement term results counter after unpublishing content.
          $original_langcode = $original_entity->get('langcode')->value;
          $this->taxonomyResultsHelper->decrementTermResultCount($original_tid, $entity_type, $bundle, $plugin_id, $original_langcode);
        }
      }
    }

    foreach ($tids as $tid) {
      if (!in_array($tid, $original_tids)) {
        if (!empty($status_field) && $new_status) {
          // Increment new term results counter in case the content is published.
          $langcode = $entity->get('langcode')->value;
          $this->taxonomyResultsHelper->incrementTermResultCount($tid, $entity_type, $bundle, $plugin_id, $langcode);
        }
        if (empty($status_field)) {
          // Increment new term results counter.
          $langcode = $entity->get('langcode')->value;
          $this->taxonomyResultsHelper->incrementTermResultCount($tid, $entity_type, $bundle, $plugin_id, $langcode);
        }
      }

      if (!empty($status_field) && $new_status && !$original_status && in_array($tid, $original_tids)) {
        // Increment term results counter after publishing content.
        $langcode = $entity->get('langcode')->value;
        $this->taxonomyResultsHelper->incrementTermResultCount($tid, $entity_type, $bundle, $plugin_id, $langcode);
      }
    }
  }

}
