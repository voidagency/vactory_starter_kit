<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Link;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Breadcrumb per node.
 */
class InternalNodeEntityBreadcrumbFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * The menu where the current page or taxonomy match has taken place.
   *
   * @var string
   */
  private $menuNames = [];

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Route provider service.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->languageManager = $container->get('language_manager');
    $instance->renderer = $container->get('renderer');
    $instance->aliasManager = $container->get('path_alias.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->menuLinkManager = $container->get('plugin.manager.menu.link');
    $instance->routeProvider = $container->get('router.route_provider');
    $instance->pathValidator = $container->get('path.validator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    /** @var \Drupal\node\Entity\Node $entity */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if (!in_array($entity_type, ['node'])) {
      return;
    }

    if ($entity->isNew()) {
      return;
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $config = \Drupal::config('vactory_decoupled_breadcrumb.settings');
    $this->menuNames = $config->get('enabled_menu') ?? [];

    // Attempt to grab links from menu.
    $links = $this->getFromMenu($entity);

    if (empty($links)) {
      // Attempt to load from path.
      $links = $this->getFromPath($entity);
    }

    if (!empty($links)) {
      $show_current_langcode = $config->get('show_current_langcode');
      if ($show_current_langcode) {
        // Add current langcode.
        array_unshift($links, Link::createFromRoute(strtoupper($langcode), '<front>', []));
      }
      $show_home = $config->get('show_home');
      if ($show_home) {
        $config_translation = $this->languageManager->getLanguageConfigOverride($langcode, 'vactory_decoupled_breadcrumb.settings');
        $home_title = $config_translation->get('home_title') ?? $config->get('home_title');
        // Add home.
        array_unshift($links, Link::createFromRoute($home_title, '<front>', []));
      }
    }

    // Format items.
    $breadcrumbs_data = [];
    /** @var \Drupal\Core\Link $link */
    $renderer = $this->renderer;
    assert($renderer instanceof RendererInterface);
    try {
      $entity = $entity->getTranslation($langcode);
    }
    catch (\InvalidArgumentException $e) {
    }
    $show_current_page = $config->get('show_current_page');
    if (!$show_current_page) {
      array_pop($links);
    }
    $breadcrumbs_data = $renderer->executeInRenderContext(new RenderContext(), static function () use ($links, $breadcrumbs_data) {
      foreach ($links as $link) {
        if ($link instanceof Link) {
          $text = $link->getText() instanceof MarkupInterface ? $link->getText()
            ->__toString() : $link->getText();
          $url = $link->getUrl()->toString();
          $url = str_replace('/backend', '', $url);
        }
        else {
          $text = $link instanceof MarkupInterface ? $link->__toString() : $link;
          $url = '#';
        }

        array_push($breadcrumbs_data, [
          'url' => $url,
          'text' => $text,
        ]);
      }
      return $breadcrumbs_data;
    });

    $this->list[0] = $this->createItem(0, $breadcrumbs_data);
  }

  /**
   * Get links form route path.
   */
  private function getFromPath($entity) {
    $links = [];
    $path = '/node/' . $entity->id();
    $alias = $this->aliasManager->getAliasByPath($path);
    if ($alias === $path) {
      $links[] = Link::fromTextAndUrl($entity->label(), $entity->toUrl());
    }
    else {
      $alias = trim($alias, '/');
      $pieces = explode('/', $alias);
      $normalized_pieces = array_map(function ($piece) {
        return ucfirst(str_replace('-', ' ', $piece));
      }, $pieces);
      $cumul = '/';
      $entity_storage = $this->entityTypeManager->getStorage('node');
      foreach ($normalized_pieces as $key => $piece) {
        $cumul .= $pieces[$key];
        $path = $this->aliasManager->getPathByAlias($cumul);
        // $found_routes = $this->routeProvider->getRoutesByPattern($path);
        // $route_iterator = $found_routes->getIterator();
        if (isset($path) && !empty($path) && $this->pathValidator->isValid($path)) {
          $matches = [];
          preg_match_all('!\d+!', $path, $matches);
          $prospect_nid = $matches[0][0] ?? NULL;
          $nid = !is_null($prospect_nid) ? (int) $prospect_nid : NULL;
          if ($nid) {
            $node = $entity_storage->load($nid);
            if ($node instanceof NodeInterface) {
              $trans_node = $this->entityRepository->getTranslationFromContext($node);
              $piece = $trans_node->label();
            }
          }
          $links[] = Link::fromTextAndUrl($piece, Url::fromUserInput($cumul));
          $cumul .= '/';
        }
        else {
          $links[] = $piece;
        }
      }
    }
    return $links;
  }

  /**
   * Get links from content type menu.
   */
  private function getFromContentTypeMenu($entity) {
    $links = [];
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $entity->type->entity;
    $original_id = $node_type->getThirdPartySetting('menu_ui', 'parent', $this->menuName . ':');
    $id = str_replace($this->menuName . ':', "", $original_id);
    $menuLinkContentStorage = $this->entityTypeManager->getStorage('menu_link_content');

    $all_menu_links = $this->menuLinkManager->getParentIds($id);

    if (empty($all_menu_links)) {
      return $links;
    }

    foreach (array_reverse($all_menu_links) as $id) {
      $plugin = $this->menuLinkManager->createInstance($id);
      $definition = $plugin->getPluginDefinition();
      $entity_id = $definition['metadata']['entity_id'];
      /** @var \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent $menuLink */
      $menuLink = $menuLinkContentStorage->load($entity_id);
      $menuLink = $this->entityRepository->getTranslationFromContext($menuLink);
      /** @var \Drupal\Core\Url $link */
      $link = $menuLink->getUrlObject();
      $attributes = $link->getOption('attributes');
      $skip = FALSE;
      if ($attributes && isset($attributes['breadcrumb']) && $attributes['breadcrumb'] === '_ignore') {
        $skip = TRUE;
      }
      if (!$skip) {
        $links[] = Link::fromTextAndUrl($menuLink->label(), $link);
      }
    }

    // Add current node.
    $links[] = Link::fromTextAndUrl($entity->label(), $entity->toUrl());

    return $links;
  }

  /**
   * Get Links from menu.
   */
  private function getFromMenu($entity) {
    $links = [];
    $menu_links = [];
    $active_link = NULL;
    $menuLinkContentStorage = $this->entityTypeManager->getStorage('menu_link_content');

    foreach ($this->menuNames as $menuName) {
      $m_links = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', [
        "node" => $entity->id(),
      ], $menuName);
      $menu_links = [...$menu_links, ...array_values($m_links)];
    }

    if (empty($menu_links)) {
      return $links;
    }

    $active_link = reset($menu_links);
    $all_menu_links = $this->menuLinkManager->getParentIds($active_link->getPluginId());

    foreach (array_reverse($all_menu_links) as $id) {
      $plugin = $this->menuLinkManager->createInstance($id);
      $definition = $plugin->getPluginDefinition();
      $entity_id = $definition['metadata']['entity_id'];
      /** @var \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent $menuLink */
      $menuLink = $menuLinkContentStorage->load($entity_id);
      $menuLink = $this->entityRepository->getTranslationFromContext($menuLink);
      /** @var \Drupal\Core\Url $link */
      $link = $menuLink->getUrlObject();
      $attributes = $link->getOption('attributes');
      $skip = FALSE;
      if ($attributes && isset($attributes['breadcrumb']) && $attributes['breadcrumb'] === '_ignore') {
        $skip = TRUE;
      }
      if (!$skip) {
        $links[] = Link::fromTextAndUrl($menuLink->label(), $link);
      }
    }

    return $links;
  }

}
