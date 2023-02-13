<?php

namespace Drupal\vactory_taxonomy_results\Services;

use Drupal\taxonomy\Entity\Term;
use Drupal\vactory_taxonomy_results\Entity\TermResultCount;

/**
 * Term result counter helper.
 */
class TermResultCounterHelper {

  public function getTermResultCount($tid, $plugin = NULL, $entity_type = NULL, $bundle = NULL) {
    $query = \Drupal::entityQuery('term_result_count')
      ->condition('tid', $tid);
    if (!empty($entity_type)) {
      $query->condition('entity_type', $entity_type);
    }
    if (!empty($bundle)) {
      $query->condition('bundle', $bundle);
    }
    if (!empty($plugin)) {
      $query->condition('plugin', $plugin);
    }

    $results = $query->execute();

    return count($results);
  }

  public function incrementTermResultCount($tid, $entity_type, $bundle, $plugin, $langcode) {
    $query = \Drupal::entityQuery('term_result_count')
      ->condition('tid', $tid)
      ->condition('entity_type', $entity_type)
      ->condition('bundle', $bundle)
      ->condition('plugin', $plugin);
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $entity_id = reset($entity_ids);
      $termResultCount = TermResultCount::load($entity_id);
      if ($termResultCount) {
        $this->updateTermResultCount2($termResultCount, $langcode);
      }
      if (!$termResultCount) {
        $this->insertTermResultCount($tid, $entity_type, $bundle, $plugin, $langcode);
      }
    }
    if (empty($entity_ids)) {
      $this->insertTermResultCount($tid, $entity_type, $bundle, $plugin, $langcode);
    }
  }

  public function decrementTermResultCount($tid, $entity_type, $bundle, $plugin, $langcode) {
    $query = \Drupal::entityQuery('term_result_count')
      ->condition('tid', $tid)
      ->condition('entity_type', $entity_type)
      ->condition('bundle', $bundle)
      ->condition('plugin', $plugin);
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $entity_id = reset($entity_ids);
      $termResultCount = TermResultCount::load($entity_id);
      if ($termResultCount) {
        $this->updateTermResultCount2($termResultCount, $langcode, 'decrement');
      }
    }
  }

  public function insertTermResultCount($tid, $entity_type, $bundle, $plugin, $langcode) {
    TermResultCount::create([
      'langcode' => $langcode,
      'tid' => $tid,
      'bundle' => $bundle,
      'entity_type' => $entity_type,
      'plugin' => $plugin,
      'count' => 1,
    ])->save();
    $termResultCountIds = \Drupal::entityQuery('term_result_count')
      ->condition('tid', $tid)
      ->condition('bundle', $bundle)
      ->condition('entity_type', $entity_type)
      ->condition('plugin', $plugin)
      ->execute();
    if (!empty($termResultCountIds)) {
      $termResultCountId = reset($termResultCountIds);
      $term = Term::load($tid);
      if ($term) {
        $value = $term->get('results_count')->getValue();
        $status = $term->get('status')->getValue();
        $value[] = [
          'target_id' => $termResultCountId,
        ];
        $term->set('results_count', $value)
          ->set('status', intval($status))
          ->save();
      }
    }
  }

  public function updateTermResultCount2(TermResultCount $termResultCount, $langcode, $op = 'increment') {
    if (!$termResultCount->hasTranslation($langcode)) {
      $termResultCount->addTranslation($langcode, [
        'count' => 1,
      ])
      ->save();
    }
    else {
      $termResultCountTranslation = \Drupal::service('entity.repository')
        ->getTranslationFromContext($termResultCount, $langcode);
      $count = $termResultCountTranslation->get('count')->value;
      $count = $op === 'increment' ? $count + 1 : max($count - 1, 0);
      $termResultCountTranslation->set('count', $count)
        ->save();
    }
  }

  public function updateTermResultCount($tid, $entity_type, $bundle, $plugin, $langcode, $count) {
    $ids = \Drupal::entityQuery('term_result_count')
      ->condition('tid', $tid)
      ->condition('langcode', $langcode)
      ->condition('bundle', $bundle)
      ->condition('entity_type', $entity_type)
      ->condition('plugin', $plugin)
      ->execute();
    if (!empty($ids)) {
      $id = reset($ids);
      $termResultCount = TermResultCount::load($id);
      $termResultCount->set('count', $count)
        ->save();
    }
  }

}
