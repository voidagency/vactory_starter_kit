<?php

namespace Drupal\vactory_business\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\NodeType;

/**
 * Extra data per node.
 */
class FicheBusinessFormUrlItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->aliasManager = $container->get('path_alias.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if (!in_array($entity_type, ['node'])) {
      return;
    }

    if ($entity->bundle() !== 'vactory_business') {
      return;
    }

    $node_type = NodeType::load('vactory_business');
    $form_nid = $node_type->getThirdPartySetting('vactory_business', 'form_page', '');
    $form_path_alias = '';
    if (!empty($form_nid)) {
      $form_path_alias = $this->aliasManager->getAliasByPath("/node/{$form_nid}");
    }

    $this->list[0] = $this->createItem(0, $form_path_alias);
  }

}
