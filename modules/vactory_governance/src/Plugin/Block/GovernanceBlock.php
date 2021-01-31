<?php

namespace Drupal\vactory_governance\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Provides a "Governance Member" block.
 *
 * @Block(
 *   id = "vactory_governance_member",
 *   admin_label = @Translation("Membre du gouvernement"),
 *   category = @Translation("Governance")
 * )
 */
class GovernanceBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $cachabelmetadata = new CacheableMetadata();
    $cachabelmetadata->addCacheContexts(['url.path']);

    $content = [];
    $vid = 'vactory_governance_role';
    $terms = \Drupal::service('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadTree($vid);
    $terms = array_values($this->load($vid));
    $content['terms'] = $terms;

    $build = [
      "#theme" => "governance_member_block",
      '#content' => $content,
    ];

    foreach ($terms as $term) {
      $cachabelmetadata->addCacheableDependency($term);
    }

    $cachabelmetadata->applyTo($build);

    return $build;

  }

  /**
   * Function Load taxonomy.
   */
  public function load($vocabulary) {
    $terms = \Drupal::service('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary);
    $tree = [];
    foreach ($terms as $tree_object) {
      $this->buildTree($tree, $tree_object, $vocabulary);
    }

    return $tree;
  }

  /**
   * Function buold tree.
   */
  protected function buildTree(&$tree, $object, $vocabulary) {
    if ($object->depth != 0) {
      return;
    }
    $tree[$object->tid] = $object;
    $tree[$object->tid]->children = [];
    $object_children = &$tree[$object->tid]->children;

    $children = \Drupal::service('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadChildren($object->tid);
    if (!$children) {
      return;
    }

    $child_tree_objects = \Drupal::service('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary, $object->tid);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->tid == $child->id()) {
          $this->buildTree($object_children, $child_tree_object, $vocabulary);
        }
      }
    }
  }

}
