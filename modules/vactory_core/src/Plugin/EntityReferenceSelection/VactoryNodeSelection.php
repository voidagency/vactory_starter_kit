<?php

namespace Drupal\vactory_core\Plugin\EntityReferenceSelection;

use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:vactory_node",
 *   label = @Translation("Vactory Node selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 1
 * )
 */
class VactoryNodeSelection extends NodeSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Add locator entity conditions based on handler settings filters.
    $handler_settings = isset($this->configuration['handler_settings']) ? $this->configuration['handler_settings'] : [];
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
