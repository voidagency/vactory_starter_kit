<?php

namespace Drupal\vactory_seo\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Sea search result per node.
 */
class SeoSearchResultFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {

    $entity = $this->getEntity();
    if ($entity->getEntityTypeId() !== 'node' || $entity->bundle() !== 'vactory_seo') {
      return;
    }
    $title = $entity->get('title')->value;
    $res = \Drupal::service('vactory_seo.search_results')->getIndexResults($title);

    $this->list[0] = $this->createItem(0, $res);
  }

}
