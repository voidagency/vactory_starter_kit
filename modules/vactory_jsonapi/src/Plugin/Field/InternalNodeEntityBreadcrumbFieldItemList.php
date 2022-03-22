<?php

namespace Drupal\vactory_jsonapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Link;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\Node;

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
  private $menuName = 'main';

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

    // Attempt to grab links from menu.
    $links = $this->getFromMenu($entity);

    if (empty($links)) {
      // Attempt to load from content type.
      $links = $this->getFromContentTypeMenu($entity);
    }

    if (empty($links)) {
      // Attempt to load from path.
      $links = $this->getFromPath($entity);
    }

    if (!empty($links)) {
      // Add home.
      array_unshift($links, Link::createFromRoute($this->t('Home', [])
        ->render(), '<front>', []));
    }

    // Format items.
    $breadcrumbs_data = [];
    /* @var \Drupal\Core\Link $link */
    $renderer = \Drupal::service('renderer');
    assert($renderer instanceof RendererInterface);
    $breadcrumbs_data = $renderer->executeInRenderContext(new RenderContext(), static function () use ($links, $breadcrumbs_data) {
      foreach ($links as $link) {
        $text = $link->getText();
        $url = $link->getUrl()->toString();
        $url = str_replace('/backend', '', $url);

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
    $links[] = Link::fromTextAndUrl($entity->label(), $entity->toUrl());
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
    $active_link = NULL;
    $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $menuLinkContentStorage = $entityTypeManager->getStorage('menu_link_content');
    $entityRepository = \Drupal::service('entity.repository');

    $menu_links = $menuLinkManager->loadLinksByRoute('entity.node.canonical', [
      "node" => $entity->id(),
    ], $this->menuName);

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
