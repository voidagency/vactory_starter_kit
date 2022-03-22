<?php

namespace Drupal\vactory_jsonapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\Node;
use Drupal\vactory_jsonapi\BlocksManager;

/**
 * Blocks per node.
 */
class InternalNodeEntityBlocksFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var Node $entity */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();
    /** @var BlocksManager $block_manager */
    $block_manager = \Drupal::service('vactory_jsonapi.blocksManager');


    if (!in_array($entity_type, ['node'])) {
      return;
    }

    $value = $block_manager->getBlocksByNode($entity->id());

    $this->list[0] = $this->createItem(0, $value);
  }
}
