<?php

namespace Drupal\vactory_jsonapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Defines a comment list class for better normalization targeting.
 */
class InternalCommentEntityFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var \Drupal\comment\Entity\Comment $entity */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if (!in_array($entity_type, ['comment'])) {
      return;
    }

    $countChilds = \Drupal::database()->select('comment_field_data', 'c')
      ->condition('pid', $entity->id(), '=')
      ->condition('status', 1)
      ->countQuery()
      ->execute()
      ->fetchField();

    $value = [
      'hasChilds' => $countChilds > 0,
      'count' => $countChilds,
    ];

    $this->list[0] = $this->createItem(0, $value);
  }
}
