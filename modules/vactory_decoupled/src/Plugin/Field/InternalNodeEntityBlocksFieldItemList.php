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

    $plugin_filter = [
      'operator' => 'NOT IN',
      'plugins' => [],
    ];

    // Exclude Banner blocks.
    $banner_blocks = \Drupal::entityTypeManager()->getStorage('block_content')
      ->loadByProperties(['type' => 'vactory_decoupled_banner']);
    if (!empty($banner_blocks)) {
      $banner_blocks_plugins = array_map(function ($banner_block) {
        return 'block_content:' . $banner_block->uuid();
      }, $banner_blocks);
      $plugin_filter['plugins'] = array_values($banner_blocks_plugins);
    }

    // Exclude Cross Content Blocks.
    array_push($plugin_filter['plugins'], 'vactory_cross_content');

    $value = $block_manager->getBlocksByNode($entity->id(), $plugin_filter);

    // @see https://api.drupal.org/api/drupal/core%21modules%21system%21tests%21modules%21entity_test%21src%21Plugin%21Field%21ComputedTestCacheableStringItemList.php/class/ComputedTestCacheableStringItemList/9.3.x?title=&title_1=&object_type=&order=title&sort=desc
    $this->list[0] = $this->createItem(0, $value);
  }
}
