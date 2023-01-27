<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;

/**
 * Metatags per node.
 */
class InternalNodeMetatagsFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Meta tag manager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $instance->entityRepository = \Drupal::getContainer()->get('entity.repository');
    $instance->metatagManager = \Drupal::getContainer()->get('metatag.manager');
    $instance->moduleHandler = \Drupal::getContainer()->get('module_handler');
    return $instance;
  }

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

    $entity = $this->entityRepository->getTranslationFromContext($entity);

    $metatags = metatag_get_default_tags($entity);

    foreach ($this->metatagManager->tagsFromEntity($entity) as $tag => $data) {
      $metatags[$tag] = $data;
    }

    $context = [
      'entity' => $entity,
    ];

    $this->moduleHandler->alter('metatags', $metatags, $context);

    $tags = $this->metatagManager->generateRawElements($metatags, $entity);
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
