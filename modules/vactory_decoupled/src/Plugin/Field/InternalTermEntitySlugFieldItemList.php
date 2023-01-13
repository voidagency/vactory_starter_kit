<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\vactory_core\SlugManager;

/**
 * Term slug.
 */
class InternalTermEntitySlugFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * slug manager service.
   *
   * @var SlugManager
   */
  protected $slugManager;

  /**
   * Entity repository service.
   *
   * @var EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityRepository = $container->get('entity.repository');
    $instance->slugManager = $container->get('vactory_core.slug_manager');
    return $instance;
  }

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

    $term = $this->entityRepository->getTranslationFromContext($entity);

    $this->list[0] = $this->createItem(0, $this->slugManager->taxonomy2Slug($term));
  }
}
