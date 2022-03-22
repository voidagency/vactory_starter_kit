<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class BlocksController extends ControllerBase {

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
   * {@inheritdoc}
   */
  public function __construct(BlockManagerInterface $block_manager, ThemeManagerInterface $theme_manager) {
    $this->pluginManagerBlock = $block_manager;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $visible_blocks = [];
    $blocks = $this->getBlocks();

    if (!empty($blocks)) {
      foreach ($blocks as $id => $block) {
        $content = $this->getContent($block->getPluginId());
        $block_visibility = $this->formatVisibility($block->getVisibility());

        $block_data = [
          'id'            => $block->getOriginalId(),
          'label'         => $block->label(),
          'region'        => $block->getRegion(),
          'plugin'        => $block->getPluginId(),
          'weight'        => $block->getWeight(),
          'visibility'    => $block_visibility,
          'block_content' => $content,
          'settings'      => $block->get('settings'),
        ];

        array_push($visible_blocks, $block_data);
      }
    }

    $data = [
      'blocks' => $visible_blocks,
    ];

    return new JsonResponse($data);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatVisibility($visibility) {
    $siteConfig = \Drupal::config('system.site');
    $front_uri = $siteConfig->get('page.front');
    $homepage_url = Url::fromUserInput($front_uri)->toString();

    if (isset($visibility['request_path']['pages'])) {
      $visibility['request_path']['pages'] = explode("\r\n", $visibility['request_path']['pages']);

      $visibility['request_path']['pages'] = array_map(function ($uri) use ($homepage_url) {
        $url = Url::fromUserInput($uri)->toString();

        // Handle homepage.
        if ($homepage_url === $url) {
          $url = Url::fromRoute('<front>')->toString();
        }

        $url = str_replace('/backend', '', $url);
        return $url;
      }, $visibility['request_path']['pages']);
    }

    return $visibility;
  }

  /**
   * @param string $plugin
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getContent($plugin) {
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
   * @return \Drupal\Core\Entity\EntityInterface
   *   Content block entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getContentBlock($uuid) {
    $contentBlock = $this->entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties(['uuid' => $uuid]);

    if (!empty($contentBlock)) {
      if (is_array($contentBlock)) {
        $contentBlock = reset($contentBlock);
        $contentBlock = \Drupal::service('jsonapi_extras.entity.to_jsonapi')
          ->normalize($contentBlock)['data'];
      }
      return $contentBlock;
    }

    return NULL;
  }

  /**
   * Block objects list.
   *
   * @return array
   *   Blocks array.
   */
  protected function getBlocks() {
    $blocksManager = $this->entityTypeManager()->getStorage('block');
    $theme = $this->themeManager->getActiveTheme()->getName();

    $regions = system_region_list($theme, BlockRepositoryInterface::REGIONS_VISIBLE);

    $blocks = [];
    foreach ($regions as $key => $region) {
      $region_blocks = $blocksManager->loadByProperties(
        [
          'theme'  => $theme,
          'region' => $key,
        ]
      );

      if (!empty($region_blocks)) {
        $region_blocks = (array) $region_blocks;
        $blocks = array_merge($blocks, $region_blocks);
      }
    }

    return $blocks;
  }

}
