<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Url;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

class MenusController extends ControllerBase {

  /**
   * A instance of the config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Entity repository service.
   *
   * @var EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * A list of menu items.
   *
   * @var array
   */
  protected $menuItems = [];

  /**
   * The maximum depth we want to return the tree.
   *
   * @var int
   */
  protected $maxDepth = 0;

  /**
   * The minimum depth we want to return the tree from.
   *
   * @var int
   */
  protected $minDepth = 1;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MenuLinkTreeInterface $menuLinkTree,
    EntityRepositoryInterface $entityRepository
  ) {
    $this->configFactory = $config_factory;
    $this->menuLinkTree = $menuLinkTree;
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('menu.link_tree'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index(Request $request) {
    $menu_name = $request->query->get('menu_name');
    if (empty($menu_name)) {
      throw new NotFoundHttpException('Unable to work with empty menu_name. Please send a ?menu_name query string parameter with your request.');
    }
    // Setup variables.
    $this->setup($request);

    // Create the parameters.
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();

    if (!empty($this->maxDepth)) {
      $parameters->setMaxDepth($this->maxDepth);
    }

    if (!empty($this->minDepth)) {
      $parameters->setMinDepth($this->minDepth);
    }

    // Load the tree based on this set of parameters.
    $menu_tree = $this->menuLinkTree;
    $tree = $menu_tree->load($menu_name, $parameters);

    // Return if the menu does not exist or has no entries.
    if (empty($tree)) {
      return new JsonResponse([
        'items' => [],
        'json' => json_encode([]),
      ]);
    }

    // Transform the tree using the manipulators you want.
    $manipulators = [
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);

    // Finally, build a renderable array from the transformed tree.
    $menu = $menu_tree->build($tree);

    // Return if the menu has no entries.
    if (empty($menu['#items'])) {
      return new JsonResponse([
        'items' => [],
        'json' => json_encode([]),
      ]);
    }

    $this->getMenuItems($menu['#items'], $this->menuItems);

    $data = [
      'items' => array_values($this->menuItems),
      'json' => json_encode(array_values($this->menuItems)),
    ];

    return new JsonResponse($data);
  }

  /**
   * Generate the menu tree we can use in JSON.
   *
   * @param array $tree
   *   The menu tree.
   * @param array $items
   *   The already created items.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMenuItems(array $tree, array &$items = []) {
    // Loop through the menu items.
    foreach ($tree as $item_value) {
      $decoupled_link_url = '';
      $menu_entity = $item_value['original_link'] ? $item_value['original_link']->getEntity() : NULL;
      if ($menu_entity) {
        $decoupled_link = $menu_entity->get('decoupled_link')->uri;
        if ($decoupled_link) {
          $decoupled_link_url = Url::fromUri($decoupled_link)->toString(TRUE)->getGeneratedUrl();
        }
      }
      /* @var $org_link \Drupal\Core\Menu\MenuLinkInterface */
      $org_link = $item_value['original_link'];

      $newValue = $this->getElementValue($org_link);

      if (!empty($decoupled_link_url)) {
        // Use translated link when exists.
        $newValue['url'] = $decoupled_link_url;
      }

      if (!empty($item_value['below'])) {
        $newValue['below'] = [];
        $this->getMenuItems($item_value['below'], $newValue['below']);
      }

      $items[] = $newValue;
    }
  }

  /**
   * Generate the menu element value.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $link
   *   The link from the menu.
   *
   * @return array
   */
  protected function getElementValue(MenuLinkInterface $link) {
    $siteConfig = $this->configFactory->get('system.site');
    $entityRepo = $this->entityRepository;
    $front_uri = $siteConfig->get('page.front');
    $returnArray = [];

    // Load entity.
    $uuid = $link->getDerivativeId();
    if (empty($uuid)) {
      $uuid = $link->getBaseId();
    }
    $entity = $entityRepo->loadEntityByUuid('menu_link_content', $uuid);
    $entity = $entityRepo->getTranslationFromContext($entity);

    // Id.
    $returnArray['id'] = $uuid;

    // Title.
    $returnArray['title'] = $link->getTitle();

    // Options.
    $returnArray['options'] = $link->getOptions();

    // URL.
    $url = $link->getUrlObject()->toString();
    $homepage_url = Url::fromUserInput($front_uri)->toString();

    // Handle homepage.
    if ($homepage_url === $url) {
      $url = Url::fromRoute('<front>')->toString();
    }

    $url = str_replace('/backend', '', $url);

    $returnArray['url'] = $url;

    $this->moduleHandler()->alter('menu_api', $returnArray, $link, $link->getMenuName());

    return $returnArray;
  }

  /**
   * This function is used to generate some variables we need to use.
   *
   * These variables are available in the url.
   *
   * @param $request
   */
  private function setup($request) {

    // Get and set the max depth if available.
    $max = $request->get('max_depth');
    if (!empty($max)) {
      $this->maxDepth = $max;
    }

    // Get and set the min depth if available.
    $min = $request->get('min_depth');
    if (!empty($min)) {
      $this->minDepth = $min;
    }
  }
}
