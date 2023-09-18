<?php

namespace Drupal\vactory_tender\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 *  Defines the 'tender_form_url' field item list class.
 */
class TenderFormEntityFieldItemList extends FieldItemList
{


  /**
   * Language manager service.
   */
  protected $languageManager;
  /**
   * Vactory Dev Tools.
   */
  protected $vactoryDevTools;

  /**
   * Entity repository service.
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
    $instance->vactoryDevTools = $container->get('vactory_core.tools');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $bundle = $entity->bundle();
    if ($bundle != 'vactory_tender') {
      return;
    }

    $type = NodeType::load($bundle);
    $tender_nid = $type->getThirdPartySetting('vactory_tender', 'tender_node', '');
    if ($tender_nid == '') {
      return;
    }
    $node = Node::load($tender_nid);
    if (isset($node)) {
      $entity_repository = $this->entityRepository;
      $current_lang = $this->languageManager->getCurrentLanguage()->getId();
      if ($node->hasTranslation($current_lang)) {
        $node_trans = $entity_repository->getTranslationFromContext($node);
        $entity_tans = $entity_repository->getTranslationFromContext($entity);
        if (isset($node_trans) && isset($entity_tans)) {
          $tender_id_crypted = $this->vactoryDevTools->encrypt('vactory_tender:' . $entity_tans->id());
          $this->list[0] = $this->createItem(0, $node_trans->toUrl()->setRouteParameter('title', $entity_tans->label())
            ->setRouteParameter('tender', $tender_id_crypted)
            ->toString());
        }
      }
    }
  }

}
