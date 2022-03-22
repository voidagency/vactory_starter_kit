<?php

namespace Drupal\vactory_jsonapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\taxonomy\Entity\Term;

/**
 * Term slug.
 */
class InternalTermEntitySlugFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var Term $entity */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if (!in_array($entity_type, ['taxonomy_term'])) {
      return;
    }

    if ($entity->isNew()) {
      return;
    }

    $entityRepository = \Drupal::service('entity.repository');
    $slugManager = \Drupal::service('vactory_core.slug_manager');

    $term = $entityRepository
      ->getTranslationFromContext($entity);

    $this->list[0] = $this->createItem(0, $slugManager->taxonomy2Slug($term));
  }
}
