<?php

namespace Drupal\vactory_core\Controller;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\block\Controller\BlockLibraryController;

/**
 * Provides a list of block plugins to be added to the layout.
 */
class VactoryBlockController extends BlockLibraryController {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * VactoryBlockController constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository
   *   The context repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager
   *   The local action manager.
   */
  public function __construct(BlockManagerInterface $block_manager, LazyContextRepository $context_repository, RouteMatchInterface $route_match, LocalActionManagerInterface $local_action_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($block_manager, $context_repository, $route_match, $local_action_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Shows a list of blocks that can be added to a theme's layout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $theme
   *   Theme key of the block list.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listBlocks(Request $request, $theme) {
    $block_storage = $this->entityTypeManager->getStorage('block_content');

    // Since modals do not render any other part of the page, we need to render
    // them manually as part of this listing.
    if ($request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal') {
      $build['local_actions'] = $this->buildLocalActions();
    }

    $headers = [
      ['data' => $this->t('Block')],
      ['data' => $this->t('Category')],
      ['data' => $this->t('Twig Machine Name')],
      ['data' => $this->t('Operations')],
    ];

    // Only add blocks which work without any available context.
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    // Order by category, and then by admin label.
    $definitions = $this->blockManager->getSortedDefinitions($definitions);
    // Filter out definitions that are not intended to be placed by the UI.
    $definitions = array_filter($definitions, function (array $definition) {
      return empty($definition['_block_ui_hidden']);
    });

    $region = $request->query->get('region');
    $weight = $request->query->get('weight');
    $rows = [];
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $row = [];
      $row['title']['data'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $plugin_definition['admin_label'],
        ],
      ];

      $row['category']['data'] = $plugin_definition['category'];
      $row['twig_machine_name']['data'] = $this->getMachineName($plugin_id, $plugin_definition , $block_storage);
      $links['add'] = [
        'title' => $this->t('Place block'),
        'url' => Url::fromRoute('block.admin_add', [
          'plugin_id' => $plugin_id,
          'theme' => $theme,
        ]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 900,
          ]),
        ],
      ];
      if ($region) {
        $links['add']['query']['region'] = $region;
      }
      if (isset($weight)) {
        $links['add']['query']['weight'] = $weight;
      }
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }

    $build['#attached']['library'][] = 'block/drupal.block.admin';

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['block-filter-text'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $build['blocks'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No blocks available.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];

    return $build;
  }

  /**
   * Get human-readable names.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   *
   * @return array
   *   Rendered HTML.
   */
  public function getMachineName($plugin_id, array $plugin_definition, $block_storage) {

    if (strpos($plugin_id, 'views') !== FALSE) {
      $twig_machine_name = str_replace("-", ":", $plugin_id);
      $twig_machine_name = str_replace("views_block", "views", $twig_machine_name);
      $twig_machine_name = explode(":", $twig_machine_name);
      return [
        '#type' => 'inline_template',
        '#template' => "<b>Param 1 :</b> views<br><b>Param 2 :</b> $twig_machine_name[1]<br><b>Param 3 :</b> $twig_machine_name[2]",
      ];
    }
    elseif (strpos($plugin_id, '_block') !== FALSE) {
      return [
        '#type' => 'inline_template',
        '#template' => "<b>Param 1 :</b> block<br><b>Param 2 :</b> $plugin_id",
      ];
    }
    elseif (strpos($plugin_id, 'entity_') !== FALSE) {
      $twig_machine_name = str_replace("entity_view", "entity", $plugin_id);
      $twig_machine_name = explode(":", $twig_machine_name);
      return [
        '#type' => 'inline_template',
        '#template' => "<b>Param 1 :</b> $twig_machine_name[0]<br><b>Param 2 :</b> $twig_machine_name[1]",
      ];
    }
    elseif (strpos($plugin_id, 'block_content') !== FALSE) {
      $twig_machine_name = str_replace("block_content", "block", $plugin_definition['config_dependencies']['content'][0]);
      $twig_machine_name = explode(":", $twig_machine_name);
      $uuid = $twig_machine_name[2];
      $block = $block_storage->loadByProperties(['uuid' => $uuid]);

      $block_id = '';
      if (is_array($block) && reset($block) instanceof BlockContent) {
        $block = reset($block);
        if ($block->hasField('block_machine_name')) {
          $block_id = $block->get('block_machine_name')->value;
        }

        if (!$block_id) {
          $block_id = t('!! Go to this block and set a value for machine name');
        }
      }

      return [
        '#type' => 'inline_template',
        '#template' => "<b>Param 1 :</b> $twig_machine_name[0]<br><b>Param 2 :</b> $block_id",
      ];
    }

    return [
      '#type' => 'inline_template',
      '#template' => "<b>Param 1 :</b> block<br> <b>Param 2 :</b> $plugin_id",
    ];
  }

}
