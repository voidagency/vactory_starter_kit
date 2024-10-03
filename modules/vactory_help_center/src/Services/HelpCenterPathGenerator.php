<?php

namespace Drupal\vactory_help_center\Services;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\pathauto\AliasCleanerInterface;

/**
 * Service class for generating Help Center URL aliases.
 */
class HelpCenterPathGenerator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The pathauto alias cleaner.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * Constructs a new HelpCenterPathGenerator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\pathauto\AliasCleanerInterface $alias_cleaner
   *   The pathauto alias cleaner.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AliasCleanerInterface $alias_cleaner) {
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasCleaner = $alias_cleaner;
  }

  /**
   * Generates an alias for a Help Center node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   The generated alias.
   */
  public function generateAlias(NodeInterface $node) {
    $alias_parts = [];

    // Get the selected section term.
    $section_terms = $node->get('field_section')->referencedEntities();
    if (!empty($section_terms)) {
      $section_term = reset($section_terms);
      $alias_parts = $this->getTermHierarchy($section_term);
    }

    return implode('/', $alias_parts);
  }

  /**
   * Gets the full hierarchy of a term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term.
   *
   * @return array
   *   An array of term names representing the hierarchy.
   */
  private function getTermHierarchy(TermInterface $term) {
    $hierarchy = [];
    $current_term = $term;

    while ($current_term) {
      $hierarchy[] = $this->aliasCleaner->cleanString($current_term->getName());
      $parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadParents($current_term->id());
      $current_term = reset($parents) ?: NULL;
    }

    return array_reverse($hierarchy);
  }

}
