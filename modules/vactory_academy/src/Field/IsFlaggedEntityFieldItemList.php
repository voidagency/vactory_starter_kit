<?php

namespace Drupal\vactory_academy\Field;

use Drupal;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Item list for a computed is_flagged field.
 *
 */
class IsFlaggedEntityFieldItemList extends FieldItemList
{
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $bundle = $entity->bundle();
    if ($bundle != 'vactory_academy') {
        return;
    }
    $ids = \Drupal::entityQuery('flagging')
        ->condition('flag_id', 'favorite_academy')
        ->condition('uid', Drupal::currentUser()->id())
        ->condition('entity_id', $entity->id())
        ->execute();

    $this->list[0] = $this->createItem(0, $ids ? true : false);
  }

}
