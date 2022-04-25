<?php

namespace Drupal\vactory_core\Plugin\EntityReferenceSelection;

use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides specific access control for the taxonomy entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:vactory_term",
 *   label = @Translation("Vactory Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "default",
 *   weight = 1
 * )
 */
class VactoryTermSelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Add terms conditions based on handler settings filters.
    $handler_settings = $this->configuration;
    if (!isset($handler_settings['filter'])) {
      return $query;
    }
    $filter_settings = $handler_settings['filter'];
    foreach ($filter_settings as $field_name => $value) {
      $query->condition($field_name, $value, '=');
    }
    return $query;
  }

}
