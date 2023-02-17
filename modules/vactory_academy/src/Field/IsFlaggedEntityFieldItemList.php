<?php

namespace Drupal\vactory_academy\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;


/**
 * Item list for a computed is_flagged field.
 *
 */
class IsFlaggedEntityFieldItemList extends FieldItemList
{
  use ComputedItemListTrait;

  protected ?CacheableMetadata $cacheMetadata = NULL;

  /**
   * Flag Services.
   *
   * @var \Drupal\vactory_academy\AcademyFlagService
   */
  protected $flagAcademy;

  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->flagAcademy = $container->get('vactory_academy.flag');
    $instance->cacheMetadata = new CacheableMetadata();
    return $instance;
  }


  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $bundle = $entity->bundle();
    if ($bundle != 'vactory_academy') {
        return;
    }

    $this->cacheMetadata->addCacheContexts(['user']);
    $this->cacheMetadata->addCacheTags(['flagging_list']);

    $this->list[0] = $this->createItem(0, $this->flagAcademy->isCurrentUserFlaggedNode($entity));
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    $access = parent::access($operation, $account, TRUE);

    if ($return_as_object) {
      /** @see \Drupal\jsonapi\JsonApiResource\ResourceIdentifier */
      /** @see \Drupal\jsonapi\Normalizer\ResourceIdentifierNormalizer */
      /** @see \Drupal\jsonapi\Normalizer\ResourceObjectNormalizer::serializeField() */
      $this->ensureComputedValue();
      \assert($this->cacheMetadata instanceof CacheableMetadata);
      $access->addCacheableDependency($this->cacheMetadata);

      return $access;
    }

    return $access->isAllowed();
  }

}
