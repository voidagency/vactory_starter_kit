<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\Node;

/**
 * Metatags per node.
 */
class InternalNodeMetatagsFieldItemList extends FieldItemList
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

    if (!in_array($entity_type, ['node'])) {
      return;
    }

    if ($entity->isNew()) {
      return;
    }

    $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity);

    $metatag_manager = \Drupal::service('metatag.manager');
    $metatags = metatag_get_default_tags($entity);

    foreach ($metatag_manager->tagsFromEntity($entity) as $tag => $data) {
      $metatags[$tag] = $data;
    }

    $context = [
      'entity' => $entity,
    ];

    \Drupal::service('module_handler')->alter('metatags', $metatags, $context);

    $tags = $metatag_manager->generateRawElements($metatags, $entity);
    $normalized_tags = [];
    foreach ($tags as $key => $tag) {
      $normalized_tags[] = [
        'id' => $key,
        'tag' => $tag['#tag'],
        'attributes' => $tag['#attributes'],
      ];
    }

    $this->list[0] = $this->createItem(0, $normalized_tags);
  }
}
