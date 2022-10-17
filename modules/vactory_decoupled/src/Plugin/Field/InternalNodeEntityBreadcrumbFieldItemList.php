<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\path_alias\AliasManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

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
   * {@inheritdoc}
   */
  protected function computeValue() {
    /** @var Node $entity */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if (!in_array($entity_type, ['node'])) {
      return;
    }

    if ($entity->isNew()) {
      return;
    }

    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $config = \Drupal::config('vactory_decoupled_breadcrumb.settings');
    $this->menuNames = $config->get('enabled_menu');

    // Attempt to grab links from menu.
    $links = $this->getFromMenu($entity);

    // if (empty($links)) {
      // Attempt to load from content type.
    //  $links = $this->getFromContentTypeMenu($entity);
    // }

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
        $config_translation = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'vactory_decoupled_breadcrumb.settings');
        $home_title = $config_translation->get('home_title') ?? $config->get('home_title');
        // Add home.
        array_unshift($links, Link::createFromRoute($home_title, '<front>', []));
      }
    }

    // Format items.
    $breadcrumbs_data = [];
    /* @var \Drupal\Core\Link $link */
    $renderer = \Drupal::service('renderer');
    assert($renderer instanceof RendererInterface);
    try {
      $entity = $entity->getTranslation($langcode);
    }
    catch (\InvalidArgumentException $e) {}
    $show_current_page = $config->get('show_current_page');
    if (!$show_current_page) {
      array_pop($links);
    }
    $breadcrumbs_data = $renderer->executeInRenderContext(new RenderContext(), static function () use ($links, $breadcrumbs_data) {
      foreach ($links as $link) {
        if ($link instanceof Link) {
          $text = $link->getText() instanceof MarkupInterface ? $link->getText()->__toString() : $link->getText();
          $url = $link->getUrl()->toString();
          $url = str_replace('/backend', '', $url);
        }
        else {
          $text = $link instanceof MarkupInterface ? $link->__toString() : $link;
          $url = '#';
        }

        array_push($breadcrumbs_data, [
          'url'  => $url,
          'text' => $text,
        ]);
      }
      return $breadcrumbs_data;
    });

    $this->list[0] = $this->createItem(0, $breadcrumbs_data);
  }

  private function getFromPath($entity) {
    $links = [];
    $path = '/node/'. $entity->id();
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath($path);
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
      /** @var AliasManager $alias_manager */
      $alias_manager = \Drupal::service('path_alias.manager');
      /** @var RouteProvider $route_provider */
      $route_provider = \Drupal::service('router.route_provider');
      foreach ($normalized_pieces as $key => $piece) {
        $cumul .= $pieces[$key];
        $path = $alias_manager->getPathByAlias($cumul);
        $found_routes = $route_provider->getRoutesByPattern($path);
        $route_iterator = $found_routes->getIterator();
        if (count($route_iterator)) {
          $links[] = Link::fromTextAndUrl(t($piece), Url::fromUserInput($cumul));
          $cumul .= '/';
        }
        else {
          $links[] = t($piece);
        }
      }
    }
    return $links;
  }


  private function getFromContentTypeMenu($entity) {
    $links = [];
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $entity->type->entity;
    $original_id = $node_type->getThirdPartySetting('menu_ui', 'parent', $this->menuName . ':');
    $id = str_replace($this->menuName . ':', "", $original_id);
    $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $menuLinkContentStorage = $entityTypeManager->getStorage('menu_link_content');
    $entityRepository = \Drupal::service('entity.repository');

    $all_menu_links = $menuLinkManager->getParentIds($id);

    if (empty($all_menu_links)) {
      return $links;
    }

    foreach (array_reverse($all_menu_links) as $id) {
      $plugin = $menuLinkManager->createInstance($id);
      $definition = $plugin->getPluginDefinition();
      $entity_id = $definition['metadata']['entity_id'];
      /* @var \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent $menuLink */
      $menuLink = $menuLinkContentStorage->load($entity_id);
      $menuLink = $entityRepository->getTranslationFromContext($menuLink);
      /* @var \Drupal\Core\Url $link */
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

  private function getFromMenu($entity) {
    $links = [];
    $menu_links = [];
    $active_link = NULL;
    $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $menuLinkContentStorage = $entityTypeManager->getStorage('menu_link_content');
    $entityRepository = \Drupal::service('entity.repository');

    foreach ($this->menuNames as $menuName) {
      $m_links = $menuLinkManager->loadLinksByRoute('entity.node.canonical', [
        "node" => $entity->id(),
      ], $menuName);
      $menu_links = [...$menu_links, ...array_values($m_links)];
    }


    if (empty($menu_links)) {
      return $links;
    }

    $active_link = reset($menu_links);
    $all_menu_links = $menuLinkManager->getParentIds($active_link->getPluginId());

    foreach (array_reverse($all_menu_links) as $id) {
      $plugin = $menuLinkManager->createInstance($id);
      $definition = $plugin->getPluginDefinition();
      $entity_id = $definition['metadata']['entity_id'];
      /* @var \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent $menuLink */
      $menuLink = $menuLinkContentStorage->load($entity_id);
      $menuLink = $entityRepository->getTranslationFromContext($menuLink);
      /* @var \Drupal\Core\Url $link */
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
