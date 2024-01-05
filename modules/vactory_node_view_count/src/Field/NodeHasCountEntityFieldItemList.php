<?php

namespace Drupal\vactory_node_view_count\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\NodeType;

/**
 * Item list for a computed has_view_count field.
 */
class NodeHasCountEntityFieldItemList extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $bundle = $entity->bundle();
    $type = NodeType::load($bundle);
    $view_count_enabled = $type->getThirdPartySetting('vactory_node_view_count', 'enabling_count_node', '');
    $this->list[0] = $this->createItem(0, (bool) $view_count_enabled);
  }

}
