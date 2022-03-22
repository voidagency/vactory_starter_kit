<?php

namespace Drupal\vactory_jsonapi;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\node\Entity\Node;

/**
 * {@inheritdoc}
 */
class BlocksManager
{
  use ConditionAccessResolverTrait;

  /**
   * Drupal\Core\Block\BlockManagerInterface definition.
   *
   * @var BlockManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * Drupal\Core\Theme\ThemeManagerInterface definition.
   *
   * @var ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The condition plugin manager.
   *
   * @var ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * The JSON:API version generator of an entity..
   *
   * @var EntityToJsonApi
   */
  protected $entityToJsonApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    BlockManagerInterface $block_manager,
    ThemeManagerInterface $theme_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ExecutableManagerInterface $condition_plugin_manager,
    EntityToJsonApi $entity_to_jsonapi
  )
  {
    $this->pluginManagerBlock = $block_manager;
    $this->themeManager = $theme_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->conditionPluginManager = $condition_plugin_manager;
    $this->entityToJsonApi = $entity_to_jsonapi;
  }

  public function getBlocksByNode($nid)
  {
    $blocks = [];

    if (!$nid) {
      return $blocks;
    }

    try {
      $blocks = $this->getThemeBlocks();
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }

    usort($blocks, function ($item1, $item2) {
      return $item1['weight'] <=> $item2['weight'];
    });

    return $this->getVisibleBlocks($blocks, $nid);
  }

  /**
   * Access control handler for the block.
   *
   * @param $blocks
   * @param $nid
   * @return array
   */
  protected function getVisibleBlocks($blocks, $nid)
  {
    $node = Node::load($nid);
    $path = '/node/' . $nid;
    $visible_blocks = [];
    foreach ($blocks as $block) {
      $conditions = [];
      foreach ($block['visibilityConditions'] as $condition_id => $condition) {
        if ($condition_id === 'decoupled_request_path') {
          $condition->setContextValue('path', $path);
        } else if (in_array($condition_id, ['entity_bundle:node', 'node_type'])) {
          $condition->setContextValue('node', $node);
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
   *
   * @return array
   *   Blocks array.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  protected function getThemeBlocks()
  {
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
        $region_blocks = (array)$region_blocks;
        $blocks_list = array_merge($blocks_list, $region_blocks);
      }
    }

    $blocks = array_map(function ($block) use ($conditionPluginManager) {
      $visibility = $block->getVisibility();

      if (isset($visibility['request_path'])) {
        $visibility['decoupled_request_path'] = $visibility['request_path'];
        $visibility['decoupled_request_path']['id'] = 'decoupled_request_path';
        unset($visibility['request_path']);
      }

      $visibilityCollection = new ConditionPluginCollection($conditionPluginManager, $visibility);

      return [
        'id' => $block->getOriginalId(),
        'label' => $block->label(),
        'region' => $block->getRegion(),
        'plugin' => $block->getPluginId(),
        'weight' => $block->getWeight(),
        'content' => $this->getContent($block->getPluginId()),
        'visibilityConditions' => $visibilityCollection,
      ];
    }, $blocks_list);

    return $blocks;
  }

  /**
   * @param string $plugin
   *
   * @return array
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  protected function getContent(string $plugin)
  {
    $data = [];

    if (strpos($plugin, ':') !== FALSE) {
      list($plugin_type, $plugin_uuid) = explode(':', $plugin);
      if ($plugin_type === 'block_content') {
        $data = $this->getContentBlock($plugin_uuid);
      }
    }

    return $data;
  }

  /**
   * Content block entity.
   *
   * @param string $uuid
   *   Content block UUID.
   *
   * @return EntityInterface
   *   Content block entity.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function getContentBlock(string $uuid)
  {
    $contentBlock = $this->entityTypeManager
      ->getStorage('block_content')
      ->loadByProperties(['uuid' => $uuid]);

    if (!empty($contentBlock)) {
      if (is_array($contentBlock)) {
        $contentBlock = reset($contentBlock);
        try {
          $contentBlock = $this->entityToJsonApi
            ->normalize($contentBlock)['data']['attributes']['field_dynamic_block_components'];
        } catch (\Exception $e) {
          \Drupal::logger('vactory_jsonapi')->error('Block @block_id not found', ['@block_id' => $uuid]);
        }
      }
      return $contentBlock;
    }

    return NULL;
  }

}
