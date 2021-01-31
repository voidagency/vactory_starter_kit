<?php

namespace Drupal\vactory_annual_report\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class VactoryAnnualReportManager.
 *
 * @package Drupal\vactory_annual_report\Services
 */
class VactoryAnnualReportManager {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Function to load taxonomy term.
   */
  public function load($vocabulary) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree($vocabulary);
    $tree = [];
    foreach ($terms as $tree_object) {
      $this->buildTree($tree, $tree_object, $vocabulary);
    }
    return $tree;
  }

  /**
   * Function to load the tree of a specific taxonomy term.
   */
  public function buildTree(&$tree, $object, $vocabulary) {
    if ($object->depth != 0) {
      return;
    }
    $tree[$object->tid] = $object;
    $tree[$object->tid]->children = [];
    $object_children = &$tree[$object->tid]->children;
    $children = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadChildren($object->tid);
    if (!$children) {
      return;
    }
    $child_tree_objects = $this->entityTypeManager->getStorage('taxonomy_term')
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
