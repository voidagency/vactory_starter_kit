<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\Node;
use Drupal\vactory_decoupled\BlocksManager;

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
    $block_manager = \Drupal::service('vactory_decoupled.blocksManager');


    if (!in_array($entity_type, ['node'])) {
      return;
    }

    // Exclude Banner blocks.
    $banner_plugin_filter = [];
    $banner_blocks = \Drupal::entityTypeManager()->getStorage('block_content')
      ->loadByProperties(['type' => 'vactory_decoupled_banner']);
    if (!empty($banner_blocks)) {
      $banner_blocks_plugins = array_map(function ($banner_block) {
        return 'block_content:' . $banner_block->uuid();
      }, $banner_blocks);
      $banner_plugin_filter = [
        'operator' => 'NOT IN',
        'plugins' => array_values($banner_blocks_plugins),
      ];
    }

    $value = $block_manager->getBlocksByNode($entity->id(), $banner_plugin_filter);

    $this->list[0] = $this->createItem(0, $value);
  }
}
