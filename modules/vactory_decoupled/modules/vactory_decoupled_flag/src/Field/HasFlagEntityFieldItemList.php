<?php

namespace Drupal\vactory_decoupled_flag\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\NodeType;

/**
 * Item list for a computed is_flagged field.
 */
class HasFlagEntityFieldItemList extends FieldItemList {
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $bundle = $entity->bundle();
    $type = NodeType::load($bundle);
    $flag_enabled = $type->getThirdPartySetting('vactory_decoupled_flag', 'flag_enabling', '');
    $this->list[0] = $this->createItem(0, (bool) $flag_enabled);
  }

}
