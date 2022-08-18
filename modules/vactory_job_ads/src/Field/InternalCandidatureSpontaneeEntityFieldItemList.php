<?php

namespace Drupal\vactory_job_ads\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Defines a user list class for better normalization targeting.
 */
class InternalCandidatureSpontaneeEntityFieldItemList extends FieldItemList
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
    if ($bundle != 'vactory_job_ads') {
      return;
    }
    $type = NodeType::load($bundle);
    $candidature_nid = $type->getThirdPartySetting('vactory_job_ads', 'candidature_node', '');
    if ($candidature_nid == '') {
      return;
    }
    $node = Node::load($candidature_nid);

    $this->list[0] = $this->createItem(0, $node->toUrl()->setRouteParameter('nid', $entity->id())->toString());
  }

}
