<?php

namespace Drupal\vactory_decoupled;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;

/**
 * {@inheritdoc}
 */
class BlocksManager {

  use ConditionAccessResolverTrait;

  /**
   * Drupal\Core\Block\BlockManagerInterface definition.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * Drupal\Core\Theme\ThemeManagerInterface definition.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * The JSON:API version generator of an entity..
   *
   * @var \Drupal\vactory_decoupled\JsonApiClient
   */
  protected $jsonApiClient;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * The block content storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * The account proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    BlockManagerInterface $block_manager,
    ThemeManagerInterface $theme_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ExecutableManagerInterface $condition_plugin_manager,
    JsonApiClient $json_api_client,
    ModuleHandlerInterface $moduleHandler,
    LoggerChannelFactory $logger,
    AccountProxyInterface $accountProxy
  ) {
    $this->pluginManagerBlock = $block_manager;
    $this->themeManager = $theme_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->blockContentStorage = $this->entityTypeManager->getStorage('block_content');
    $this->conditionPluginManager = $condition_plugin_manager;
    $this->jsonApiClient = $json_api_client;
    $this->moduleHandler = $moduleHandler;
    $this->logger = $logger;
    $this->accountProxy = $accountProxy;
  }

  /**
   * Get blocks by node.
   */
  public function getBlocksByNode($nid, $filter = []) {
    $blocks = [];

    if (!$nid) {
      return $blocks;
    }

    try {
      $blocks = $this->getThemeBlocks();
      // Exclude Banner blocks.
      $banner_blocks = $this->blockContentStorage->loadByProperties(['type' => 'vactory_decoupled_banner']);
      if (!empty($banner_blocks)) {
        $banner_blocks_plugins = array_map(function ($banner_block) {
          return 'block_content:' . $banner_block->uuid();
        }, $banner_blocks);
        $filter['plugins'] = array_values($banner_blocks_plugins);
      }
      if (isset($filter['operator']) && isset($filter['plugins']) && !empty($filter['plugins'])) {
        // Apply filter if exist.
        $blocks = array_filter($blocks, function ($block) use ($filter) {
          if ($filter['operator'] === 'IN') {
            return isset($block['plugin']) && in_array($block['plugin'], $filter['plugins'], TRUE);
          }
          if ($filter['operator'] === 'NOT IN') {
            return isset($block['plugin']) && !in_array($block['plugin'], $filter['plugins'], TRUE);
          }
          return TRUE;
        });
      }
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    catch (PluginNotFoundException $e) {
    }

    usort($blocks, function ($item1, $item2) {
      return (int) ($item1['weight'] <=> $item2['weight']);
    });

    return $this->getVisibleBlocks($blocks, $nid);
  }

  /**
   * Access control handler for the block.
   */
  protected function getVisibleBlocks($blocks, $nid) {
    $node = Node::load($nid);
    $path = '/node/' . $nid;
    $visible_blocks = [];
    foreach ($blocks as $block) {
      $conditions = [];
      foreach ($block['visibilityConditions'] as $condition_id => $condition) {
        if ($condition_id === 'decoupled_request_path') {
          $condition->setContextValue('path', $path);
        }
        else {
          if (in_array($condition_id, ['entity_bundle:node', 'node_type'])) {
            $condition->setContextValue('node', $node);
          }
          else {
            if ($condition_id === 'user_role') {
              $condition->setContextValue('user', $this->accountProxy->getAccount());
            }
          }
        }

        $conditions[$condition_id] = $condition;
      }

      if ($this->resolveConditions($conditions, 'and') !== FALSE) {
        unset($block['visibilityConditions']);
        $visible_blocks[] = $block;
      }
    }
    return $visible_blocks;
  }

  /**
   * Block objects list.
   */
  protected function getThemeBlocks() {
    $name = __CLASS__ . '_' . __METHOD__;
    $blocks = &drupal_static($name, []);

    if ($blocks) {
      return $blocks;
    }

    $blocksManager = $this->entityTypeManager->getStorage('block');
    $theme = $this->themeManager->getActiveTheme()->getName();
    $conditionPluginManager = $this->conditionPluginManager;
    $regions = system_region_list($theme, BlockRepositoryInterface::REGIONS_VISIBLE);

    $blocks_list = [];
    foreach ($regions as $key => $region) {
      $region_blocks = $blocksManager->loadByProperties(
        [
          'theme' => $theme,
          'region' => $key,
          'status' => 1,
        ]
      );

      if (!empty($region_blocks)) {
        $region_blocks = (array) $region_blocks;
        $blocks_list = array_merge($blocks_list, $region_blocks);
      }
    }

    $blocks_list = array_filter($blocks_list, function ($block) {
      return (strpos($block->getPluginId(), 'block_content:') !== FALSE || strpos($block->getPluginId(), 'vactory_cross_content') !== FALSE);
    });

    $blocks = array_map(function ($block) use ($conditionPluginManager) {
      $visibility = $block->getVisibility();

      if (isset($visibility['request_path'])) {
        $visibility['decoupled_request_path'] = $visibility['request_path'];
        $visibility['decoupled_request_path']['id'] = 'decoupled_request_path';
        unset($visibility['request_path']);
      }

      $visibilityCollection = new ConditionPluginCollection($conditionPluginManager, $visibility);

      // Determine block classification to distinguish between blocks.
      $classification = 'default';
      $block_content = $this->getContent($block->getPluginId());
      $block_info = [
        'id' => $block->getOriginalId(),
        'label' => $block->label(),
        'region' => $block->getRegion(),
        'plugin' => $block->getPluginId(),
        'weight' => $block->getWeight(),
        'classification' => $classification,
        'content' => $block_content['block'] ?? '',
        'visibilityConditions' => $visibilityCollection,
        'classes' => $block->getThirdPartySetting('block_class', 'classes'),
        'body_classes' => $block->getThirdPartySetting('block_page_class', 'body_classes') ?? '',
        'html_classes' => $block->getThirdPartySetting('block_page_class', 'html_classes') ?? '',
        'container' => $block->getThirdPartySetting('vactory_field', 'block_container') ?? 'narrow_width',
        'container_spacing' => $block->getThirdPartySetting('vactory_field', 'container_spacing') ?? 'small_space',
      ];
      // Invoke internal block classification alter.
      $this->moduleHandler->invokeAll('internal_block_classification_alter', [
        &$classification,
        $block_info,
      ]);
      $block_info['classification'] = $classification;
      return $block_info;
    }, $blocks_list);

    return $blocks;
  }

  /**
   * Get block content.
   */
  protected function getContent(string $plugin) {
    $data = [];

    if (strpos($plugin, ':') !== FALSE) {
      [$plugin_type, $plugin_uuid] = explode(':', $plugin);
      if ($plugin_type === 'block_content') {
        $data = $this->getContentBlock($plugin_uuid);
      }
    }

    return $data;
  }

  /**
   * Content block entity.
   */
  private function getContentBlock(string $uuid) {
    $contentBlock = $this->blockContentStorage->loadByProperties(['uuid' => $uuid]);
    if (!empty($contentBlock)) {
      if (is_array($contentBlock)) {
        $contentBlock = reset($contentBlock);

        $blockCache = [
          'cache-tags' => $contentBlock->getCacheTags(),
          'max-age' => $contentBlock->getCacheMaxAge(),
          'contexts' => $contentBlock->getCacheContexts(),
        ];
        try {
          $filters = [
            "fields" => [
              "block_content--vactory_block_component" => "block_machine_name,field_dynamic_block_components",
            ],
          ];

          $response = $this->jsonApiClient->serializeIndividual($contentBlock, $filters);
          $response_cache_tags = $response['cache']['tags'] ?? [];
          $blockCache['cache-tags'] = Cache::mergeTags($blockCache['cache-tags'], $response_cache_tags);

          $client_data = json_decode($response['data'], TRUE);

          if (isset($client_data['data']['attributes']['field_dynamic_block_components'])) {
            $contentBlock = $client_data['data']['attributes']['field_dynamic_block_components'];
          }
        }
        catch (\Exception $e) {
          $this->logger->get('vactory_decoupled')
            ->error('Block @block_id not found', ['@block_id' => $uuid]);
        }
      }
      return [
        'block' => $contentBlock,
        'cache' => $blockCache,
      ];
    }

    return NULL;
  }

}
