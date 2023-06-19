<?php

namespace Drupal\vactory_cross_content\Field;

use Drupal\block\Entity\Block;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

/**
 * Defines a vcc list class for better normalization targeting.
 */
class InternalVCCFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  // phpcs:disable
  protected ?CacheableMetadata $cacheMetadata = NULL;
  // phpcs:enable

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
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Cross content manager.
   *
   * @var \Drupal\vactory_cross_content\Services\VactoryCrossContentManager
   */
  protected $crossContentManager;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $instance = parent::createInstance($definition, $name, $parent);
    $instance->cacheMetadata = new CacheableMetadata();
    $container = \Drupal::getContainer();
    $instance->blockManager = $container->get('vactory_decoupled.blocksManager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->crossContentManager = $container->get('vactory_cross_content.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $value = [];
    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = NodeType::load($entity->bundle());
    $isEnabled = $type->getThirdPartySetting('vactory_cross_content', 'enabling', '');
    if ($entity_type !== 'node' || !$isEnabled) {
      return $value;
    }
    $value = [
      'nodes' => [],
      'more_link' => '',
      'more_link_label' => '',
      'display_mode' => '',
      'limit' => 0,
    ];
    if ($this->moduleHandler->moduleExists('vactory_decoupled')) {
      // Get vcc blocks here.
      $banner_plugin_filter = [
        'operator' => 'IN',
        'plugins' => ['vactory_cross_content'],
      ];
      $blocks = $this->blockManager->getBlocksByNode($entity->id(), $banner_plugin_filter);
      $block_info = reset($blocks);
      if (!empty($block_info)) {
        $block = Block::load($block_info['id']);
        if (!empty($block)) {
          $configuration = $block->get('settings');
          $title = $configuration['title'];
          $nbr = $configuration['nombre_elements'] ?? $type->getThirdPartySetting('vactory_cross_content', 'nombre_elements', 3);
          $more_link = $configuration['more_link'] ?? $type->getThirdPartySetting('vactory_cross_content', 'more_link', '');
          $more_link_label = $configuration['more_link_label'] ?? $type->getThirdPartySetting('vactory_cross_content', 'more_link_label', '');
          $display_mode = $configuration['display_mode'];
          $value['title'] = $title;
          $value['more_link'] = $more_link;
          $value['more_link_label'] = $more_link_label;
          $value['limit'] = $nbr;
          $value['display_mode'] = $display_mode;
          $view = $this->crossContentManager->getCrossContentView($type, $entity, $configuration);
          if (!empty($view) && is_object($view)) {
            $view->execute();
            if (!empty($view->result)) {
              $nids = array_map(function ($row) {
                return $row->nid;
              }, $view->result);
              $value['nodes'] = $nids;
            }
          }
          if (isset($value['nodes']) && !empty($value['nodes'])) {
            $nodes = $this->entityTypeManager->getStorage('node')
              ->loadMultiple($value['nodes']);
            $value['nodes'] = [];
            /** @var NodeInterface $node */
            $cacheTags = [];
            $cacheContexts = [];
            foreach ($nodes as $node) {
              $cacheTags = Cache::mergeTags($cacheTags, $node->getCacheTags());
              $cacheContexts = Cache::mergeContexts($cacheContexts, $node->getCacheContexts());

              $node_trans = $this->entityRepository->getTranslationFromContext($node);
              if (isset($node_trans)) {
                $normalized_node = [
                  'title' => $node_trans->label(),
                ];
                $context = [
                  'node' => $node_trans,
                  'node_type' => $node_trans->bundle(),
                ];
                $base_node_type = $node_trans->bundle();
                $this->moduleHandler
                  ->alter('jsonapi_vcc_normalized_node', $normalized_node, $context, $base_node_type);
                $value['nodes'][] = $normalized_node;
              }
            }
            $this->cacheMetadata->addCacheTags($cacheTags);
            $this->cacheMetadata->addCacheContexts($cacheContexts);
          }
        }
      }

    }

    $this->list[0] = $this->createItem(0, $value);
  }

  /**
   * {@inheritDoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = parent::access($operation, $account, TRUE);

    if ($return_as_object) {
      // phpcs:disable
      // Here you witness a pure hack. The thing is that JSON:API
      // Normalization does not compute cacheable metadata for
      // Computed relations like this one
      /** @see \Drupal\jsonapi\JsonApiResource\ResourceIdentifier */
      /** @see \Drupal\jsonapi\Normalizer\ResourceIdentifierNormalizer */
      // However, thanks to the access check, its result is added
      // As a cacheable dependency to the normalization.
      /** @see \Drupal\jsonapi\Normalizer\ResourceObjectNormalizer::serializeField() */
      // phpcs:enable
      $this->ensureComputedValue();
      \assert($this->cacheMetadata instanceof CacheableMetadata);
      $access->addCacheableDependency($this->cacheMetadata);

      return $access;
    }

    return $access->isAllowed();
  }

}