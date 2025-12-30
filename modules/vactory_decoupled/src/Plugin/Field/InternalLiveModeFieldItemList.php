<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Defines a user list class for better normalization targeting.
 */
class InternalLiveModeFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if ($entity_type !== "node") {
      return;
    }

    $user_id = \Drupal::currentUser()->id();
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);

    $this->list[0] = $this->createItem(0, $user->hasPermission('edit content live mode'));
  }

}
