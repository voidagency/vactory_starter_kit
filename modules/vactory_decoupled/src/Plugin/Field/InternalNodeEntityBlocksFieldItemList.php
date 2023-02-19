<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;
use Drupal\vactory_decoupled\BlocksManager;

/**
 * Blocks per node.
 */
class InternalNodeEntityBlocksFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  protected ?CacheableMetadata $cacheMetadata = NULL;

  /**
   * Block manager service.
   *
   * @var \Drupal\vactory_decoupled\BlocksManager
   */
  protected $blockManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->blockManager = $container->get('vactory_decoupled.blocksManager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->cacheMetadata = new CacheableMetadata();
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
    /** @var BlocksManager $block_manager */
    /*$block_manager = \Drupal::service('vactory_decoupled.blocksManager');*/

    if (!in_array($entity_type, ['node'])) {
      return;
    }

    $plugin_filter = [
      'operator' => 'NOT IN',
      'plugins' => [],
    ];

    // Exclude Banner blocks.
    $blockContentStorage = $this->entityTypeManager->getStorage('block_content');
    $banner_blocks = $blockContentStorage->loadByProperties(['type' => 'vactory_decoupled_banner']);
    if (!empty($banner_blocks)) {
      $banner_blocks_plugins = array_map(function ($banner_block) {
        return 'block_content:' . $banner_block->uuid();
      }, $banner_blocks);
      $plugin_filter['plugins'] = array_values($banner_blocks_plugins);
    }

    // Exclude Cross Content Blocks.
    array_push($plugin_filter['plugins'], 'vactory_cross_content');

    $value = $this->blockManager->getBlocksByNode($entity->id(), $plugin_filter, $blockContentStorage);

    // @see https://api.drupal.org/api/drupal/core%21modules%21system%21tests%21modules%21entity_test%21src%21Plugin%21Field%21ComputedTestCacheableStringItemList.php/class/ComputedTestCacheableStringItemList/9.3.x?title=&title_1=&object_type=&order=title&sort=desc
    $tags = [
      'config:block_list',
      'block_list',
      'block_content_list',
    ];
    $this->cacheMetadata->addCacheTags($tags);
    $this->moduleHandler->alter('internal_blocks_cacheability', $this->cacheMetadata, $entity, $value);
    $this->list[0] = $this->createItem(0, $value);
  }

  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    $access = parent::access($operation, $account, TRUE);

    if ($return_as_object) {
      // Here you witness a pure hack. The thing is that JSON:API
      // normalization does not compute cacheable metadata for
      // computed relations like this one.
      /** @see \Drupal\jsonapi\JsonApiResource\ResourceIdentifier */
      /** @see \Drupal\jsonapi\Normalizer\ResourceIdentifierNormalizer */
      // However, thanks to the access check, its result is added
      // as a cacheable dependency to the normalization.
      /** @see \Drupal\jsonapi\Normalizer\ResourceObjectNormalizer::serializeField() */
      $this->ensureComputedValue();
      \assert($this->cacheMetadata instanceof CacheableMetadata);
      $access->addCacheableDependency($this->cacheMetadata);

      return $access;
    }

    return $access->isAllowed();
  }

}
