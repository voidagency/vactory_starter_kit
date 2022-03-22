<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\block\BlockRepositoryInterface;
use Drupal;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\vactory_core\Vactory;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

// @todo: serve block content.
// @todo: make this only for banner block
// @todo: what about VCC ? what about blocks in menu ?

class VccController extends ControllerBase {

  /**
   * Drupal\Core\Block\BlockManagerInterface definition.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * Condition plugin.
   *
   * @var \Drupal\Core\Condition\ConditionPluginBase $condition
   */
  protected $pluginManagerCondition;

  /**
   * Drupal\Core\Theme\ThemeManagerInterface definition.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Drupal\Core\Path\PathMatcherInterface definition.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $router;

  /**
   * \Drupal\path_alias\AliasManagerInterface definition.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

  private const BLOCK_NAME = 'crosscontentblock';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManagerBlock = $container->get('plugin.manager.block');
    $instance->pluginManagerCondition = $container->get('plugin.manager.condition');
    $instance->themeManager = $container->get('theme.manager');
    $instance->pathMatcher = $container->get('path.matcher');
    $instance->router = $container->get('router.no_access_checks');
    $instance->pathAliasManager = $container->get('path_alias.manager');
    return $instance;
  }

  public function index(Request $request) {
    $path = $this->checkPath($request);

    // Check if path is valid.
    try {
      Url::fromUserInput($path)->getInternalPath();
    } catch (\Exception $e) {
      return new JsonResponse([
        'path'   => $path,
        'blocks' => [],
      ]);
    }

    $visible_blocks = [];
    $blocks = $this->getBlocks();

    if (!empty($blocks)) {
      foreach ($blocks as $id => $block) {
        $isVisible = $this->blockVisibleForPage($block, $path);

        if ($isVisible) {

          $block_data = [
            'id'            => $block->getOriginalId(),
            'label'         => $block->label(),
            'region'        => $block->getRegion(),
            'plugin'        => $block->getPluginId(),
            'weight'        => $block->getWeight(),
            'settings'      => $block->get('settings'),
          ];

          array_push($visible_blocks, $block_data);
        }
      }
    }

    $data = [
      'path'   => $path,
      'blocks' => $visible_blocks,
    ];
    $this->getRenderedData($data['path'], $data['blocks'][0]);
    return new JsonResponse($data);
  }

  /**
   * Visibility check.
   *
   * @param object $block
   *   Block object.
   * @param string $input_path
   *   Path to the checking page.
   *
   * @return bool
   *   Is the block visibility?
   */
  protected function blockVisibleForPage($block, $input_path) {
    $visibility = $block->getVisibility();
    if (empty($visibility)) {
      return TRUE;
    }

    $request_path = $visibility['request_path'] ?? NULL;
    $node_type = $visibility['node_type'] ?? NULL;

    if ($request_path) {
      return $this->checkBlockVisibilityByPath($request_path, $input_path);
    }
    else {
      if ($node_type) {
        return $this->checkBlockVisibilityByNodeType($node_type, $input_path);
      }
    }

    return FALSE;
  }

  /**
   * @param $request_path
   * @param $input_path
   *
   * @return bool
   */
  protected function checkBlockVisibilityByPath($request_path, $input_path) {
    $result = FALSE;

    try {
      $pages = mb_strtolower($request_path['pages']);
      $path = $input_path === '/' ? $input_path : rtrim($input_path, '/');
      $path_alias = mb_strtolower($this->pathAliasManager->getAliasByPath($path));
      $internal_path_alias = '/' . mb_strtolower(Url::fromUserInput($path_alias)
          ->getInternalPath());

      $result = $this->pathMatcher->matchPath($path_alias, $pages) || $this->pathMatcher->matchPath($internal_path_alias, $pages);
    } catch (\Exception $e) {

    }

    return $result;
  }

  /**
   * @param $node_type
   * @param $input_path
   *
   * @return mixed
   */
  protected function checkBlockVisibilityByNodeType($node_type, $input_path) {
    $result = FALSE;

    try {
      $match_info = $this->router->match($input_path);
      $page = $match_info['node'];

      /** @var $condition \Drupal\Core\Condition\ConditionPluginBase */
      $condition = $this->pluginManagerCondition->createInstance('node_type');
      $condition->setConfig('bundles', $node_type['bundles']);
      $condition->setConfig('negate', $node_type['negate']);
      $condition->setContextValue('node', $page);

      $result = $condition->execute();
    } catch (\Exception $e) {

    }

    return $result;
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

//    $regions = system_region_list($theme, BlockRepositoryInterface::REGIONS_VISIBLE);
    $blocks = $blocksManager->loadByProperties(
      [
        'theme'  => $theme,
        'id'     => self::BLOCK_NAME,
      ]
    );
    return $blocks;
  }

  /**
   * Check request path.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   User request.
   * @param bool $needCanonicalUrl
   *   Need call canonicalPath().
   *
   * @return string
   *   Checked path or exception if empty.
   */
  protected function checkPath(Request $request, $needCanonicalUrl = TRUE) {
    $path = $request->query->get('path');
    if (empty($path)) {
      throw new NotFoundHttpException('Unable to work with empty path. Please send a ?path query string parameter with your request.');
    }

    if ($needCanonicalUrl) {
      $path = $this->canonicalPath($path);
    }
    return $path;
  }

  /**
   * Canonical path.
   *
   * @param string $path
   *   Input path.
   * @param bool $url_path_only
   *   Only path part of url.
   *
   * @return string
   *   Canonical path.
   */
  protected function canonicalPath($path, $url_path_only = TRUE) {
    $path = mb_strtolower(trim($path));
    if ($url_path_only) {
      $path = parse_url($path, PHP_URL_PATH);
    }
    return sprintf('/%s', ltrim($path, '/'));
  }


  protected function getRenderedData($pathalias, $conf) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = null;
    $path = $this->pathAliasManager->getPathByAlias($pathalias);
    if (preg_match('/node\/(\d+)/', $path, $matches)) {
      $node = Node::load($matches[1]);
    } else {
      return [];
    }
    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = NodeType::load($node->getType());
    $settings = $conf['settings'];
    if ($type->getThirdPartySetting('vactory_cross_content', 'enabling', '') <> 1) {
      return NULL;
    }
    $taxonomy_field = $type->getThirdPartySetting('vactory_cross_content', 'taxonomy_field', '');
    $term_id = $type->getThirdPartySetting('vactory_cross_content', 'terms', '');
    $nbr = $type->getThirdPartySetting('vactory_cross_content', 'nombre_elements', 3);
    $nbr = (!empty($settings['nombre_elements'])) ? $settings['nombre_elements'] : $nbr;
    $more_link = $type->getThirdPartySetting('vactory_cross_content', 'more_link', '');
    $more_link_label = $type->getThirdPartySetting('vactory_cross_content', 'more_link_label', '');
    $view_mode = $settings['view_mode'];
    $view_mode_options = $settings['view_options'][$view_mode . '_options'];
    $display_mode = $settings['display_mode'];
    $field_name = Vactory::getFieldbyType($node, 'field_cross_content');
    $related_nodes = $field_name <> NULL ? $node->get($field_name)->value : '';
    $ignore = !empty($related_nodes);
    $id_table = 'node_field_data';
    $id_field = 'nid';
    // Configuring the Block View.
    $view = Views::getView('vactory_cross_content');
    if (!is_object($view)) {
      return [];
    }

    // Current display.
    $view->setDisplay('block_list');
    // Update plugin style.
    $view->display_handler->setOption('style', [
      'type'    => $view_mode,
      'options' => $view_mode_options,
    ]);
    $view->style_plugin = $view->display_handler->getPlugin('style');
    // Plugin style must be set before preExecute.
    $view->preExecute();
    // Set content type.
    $view->filter['type']->value = [$node->bundle() => $node->bundle()];

    // Set number of items per page.
    $view->setItemsPerPage($nbr);

    // Update view mode.
    $view->rowPlugin->options['view_mode'] = $display_mode;

    // Update more link.
    if (!empty($more_link) || !empty($settings['more_link'])) {
      $view->display_handler->overrideOption('use_more', TRUE);
      $view->display_handler->overrideOption('use_more_always', TRUE);
      $view->display_handler->overrideOption('link_display', 'custom_url');

      if (!empty($settings['more_link'])) {
        if (!empty($settings['more_link_label'])) {
          $view->display_handler
            ->overrideOption('use_more_text', $settings['more_link_label']);
        }
        $view->display_handler->overrideOption('link_url', $settings['more_link']);
      }
      else {
        if (!empty($more_link_label)) {
          $view->display_handler
            ->overrideOption('use_more_text', $more_link_label);
        }
        $view->display_handler->overrideOption('link_url', $more_link);
      }
    }
    // Get Taxonomy Stuff.
    $default_taxo = 'tid';
    $target = &$view->filter[$default_taxo];
    // In case we gathered data from the custom field.
    if ($ignore) {
      // Remove default taxonomy.
      unset($view->filter[$default_taxo]);
      // Custom query.
      $view->build($view->current_display);
      // Look for nodes.
      // If no pre-selected nodes, then get all possible nodes without this one.
      $related_nids = explode(" ", trim($related_nodes));
      $ids = array_map('intval', $related_nids);
      $view->query->addWhere(1, $id_table . '.' . $id_field, $ids, 'IN');

    }
    // Otherwise we'll use the view's filter.
    else if (!$ignore && $taxonomy_field) {
      $target->value = [];
      if (!empty($term_id)) {
        foreach ($term_id as $key => $value) {
          $target->value[$key] = $key;
        }
      }
      // Custom query.
      $view->build($view->current_display);

      $ids = [$node->get("nid")->value];
      $view->query->addWhere(1, $id_table . '.' . $id_field, $ids, '!=');
    }

    // Update views build info query.
    $view->build_info['query'] = $view->query->query();

    $view->execute();
    $this->normalize($view->result);
  }

  private function normalize($data) {
    $response = array_map( function($element) {
      $entity = $element->_entity;
      $json_n = \Drupal::service('jsonapi_extras.entity.to_jsonapi')
        ->normalize($entity);
      return $json_n;
    }, $data);
    dump($response);
  }
}
