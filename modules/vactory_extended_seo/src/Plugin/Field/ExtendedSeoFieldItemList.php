<?php

namespace Drupal\vactory_extended_seo\Plugin\Field;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Site\Settings;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;

/**
 * Extended SEO per node.
 */
class ExtendedSeoFieldItemList extends FieldItemList
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

  /**
   * Vactory decoupled helper service.
   *
   * @var \Drupal\vactory_extended_seo\VactoryExtendedSeoHelper
   */
  protected $vactoryExtendedSeoHelper;

  /**
   * Alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityRepository = $container->get('entity.repository');
    $instance->metatagManager = $container->get('metatag.manager');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->aliasManager = $container->get('path_alias.manager');
    $instance->configFactory = $container->get('config.factory');
    $instance->vactoryExtendedSeoHelper = $container->get('vactory_extended_seo.helper');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
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
    $tags = [];
    $this->vactoryExtendedSeoHelper->generateAlternate($entity->id(), $tags);
    $this->list[0] = $this->createItem(0, $tags);
  }
}
