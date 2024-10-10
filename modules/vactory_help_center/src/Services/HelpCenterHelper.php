<?php

namespace Drupal\vactory_help_center\Services;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * Service class for generating Help Center URL aliases.
 */
class HelpCenterHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The pathauto alias cleaner.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new HelpCenterPathGenerator object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AliasCleanerInterface $alias_cleaner,
    LanguageManagerInterface $language_manager,
    AliasManagerInterface $alias_manager,
    EntityRepositoryInterface $entity_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasCleaner = $alias_cleaner;
    $this->languageManager = $language_manager;
    $this->aliasManager = $alias_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Generates an alias for a Help Center node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param bool $as_array
   *   Indicate whether to return the parts as an array or as a path.
   *
   * @return string|array
   *   The generated alias.
   */
  public function generateAlias(NodeInterface $node, bool $as_array = FALSE) {
    $alias_parts = [];

    // Get the selected section term.
    $section_terms = $node->get('field_section')->referencedEntities();
    if (!empty($section_terms)) {
      $section_term = reset($section_terms);
      $alias_parts = $this->getTermHierarchy($section_term);
    }

    if ($as_array) {
      return $alias_parts;
    }

    return implode('/', $alias_parts);
  }

  /**
   * Gets the full hierarchy of a term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term.
   *
   * @return array
   *   An array of term names representing the hierarchy.
   */
  private function getTermHierarchy(TermInterface $term) {
    $hierarchy = [];
    $current_term = $term;

    while ($current_term) {
      $hierarchy[] = $this->aliasCleaner->cleanString($current_term->getName());
      $parents = $this->entityTypeManager->getStorage('taxonomy_term')->loadParents($current_term->id());
      $current_term = reset($parents) ?: NULL;
    }

    return array_reverse($hierarchy);
  }

  /**
   * Generate routers based on aliases and max depth.
   */
  public function generateRouters() {
    $config = \Drupal::config('vactory_help_center.settings');
    $aliases = $config->get('help_center_aliases');
    $nodePath = $config->get('help_center_node');
    // Delete existing help center routes.
    $max_depth = $this->getTaxonomyMaxDepth();
    $existing_routes = \Drupal::entityTypeManager()->getStorage('vactory_route')->loadByProperties([
      'path' => $nodePath,
    ]);
    foreach ($existing_routes as $existing_route) {
      $existing_route->delete();
    }

    // Generate new routes.
    foreach ($aliases as $langcode => $alias) {
      for ($depth = 1; $depth <= $max_depth; $depth++) {
        $path_parts = [];
        for ($i = 1; $i <= $depth; $i++) {
          $path_parts[] = "{help_center_item_$i}";
        }
        $route = \Drupal::entityTypeManager()->getStorage('vactory_route')->create([
          'id' => "help_center_level_{$depth}_{$langcode}",
          'label' => "Help center level {$depth}",
          'path' => $nodePath,
          'alias' => $alias . '/' . implode('/', $path_parts),
        ]);
        $route->save();
      }
    }
  }

  /**
   * Get taxonomy max depth.
   */
  private function getTaxonomyMaxDepth() {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('vactory_help_center', 0, NULL, TRUE);
    $max_depth = 0;

    foreach ($terms as $term) {
      $parents = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadParents($term->id());
      $depth = 1;

      while (!empty($parents)) {
        $depth++;
        $parent = reset($parents);
        $parents = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadParents($parent->id());
      }

      $max_depth = max($max_depth, $depth);
    }

    return $max_depth;
  }

  /**
   * Get node (Help center) breadcrumb.
   */
  public function getNodeBreadcrumb($entity) {
    $links = [];
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $node_url = \Drupal::config('vactory_help_center.settings')->get('help_center_node');
    $node_id = explode('/', $node_url);
    $node_id = end($node_id);
    $node = $this->entityTypeManager->getStorage('node')->load($node_id);
    $node_alias = $this->aliasManager->getAliasByPath($node_url, $langcode);
    $node_label = $this->entityRepository->getTranslationFromContext($node, $langcode)->label();
    $links[] = Link::fromTextAndUrl($node_label, Url::fromUserInput($node_alias));

    $hierarchy = $this->generateAlias($entity, TRUE);
    $current_path = $node_alias;
    $current_term = 0;
    foreach ($hierarchy as $item) {
      $url = "{$current_path}/{$item}";
      $current_path = $url;
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
        'term_2_slug' => $item,
        'vid' => 'vactory_help_center',
        'parent' => $current_term,
      ]);
      if (empty($terms)) {
        continue;
      }
      $term = reset($terms);
      $current_term = $term->id();
      $term_label = $this->entityRepository->getTranslationFromContext($term, $langcode)->label();
      $links[] = Link::fromTextAndUrl($term_label, Url::fromUserInput($url));
    }

    $entity_label = $this->entityRepository->getTranslationFromContext($entity, $langcode)->label();
    $entity_alias = $this->aliasManager->getAliasByPath('/node/' . $entity->id(), $langcode);
    $links[] = Link::fromTextAndUrl($entity_label, Url::fromUserInput($entity_alias));

    return $links;
  }

  /**
   * Get help center page breadcrumb.
   */
  public function getPageBreadcrumb($entity) {
    $links = [];
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $node_url = "/node/{$entity->id()}";
    $node_alias = $this->aliasManager->getAliasByPath($node_url, $langcode);
    $node_label = $this->entityRepository->getTranslationFromContext($entity, $langcode)->label();
    $links[] = Link::fromTextAndUrl($node_label, Url::fromUserInput($node_alias));

    $params = \Drupal::request()->query->all("q");

    $current_path = $node_alias;
    $current_term = 0;
    foreach ($params as $key => $item) {
      if (!str_starts_with($key, 'help_center_item_')) {
        continue;
      }
      $url = "{$current_path}/{$item}";
      $current_path = $url;
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
        'term_2_slug' => $item,
        'vid' => 'vactory_help_center',
        'parent' => $current_term,
      ]);
      if (empty($terms)) {
        continue;
      }
      $term = reset($terms);
      $current_term = $term->id();
      $term_label = $this->entityRepository->getTranslationFromContext($term, $langcode)->label();
      $links[] = Link::fromTextAndUrl($term_label, Url::fromUserInput($url));
    }

    return $links;
  }

  /**
   * Get help center page title.
   */
  public function getPageTitle($entity) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $params = \Drupal::request()->query->all("q");
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $current_term = 0;
    foreach ($params as $key => $item) {
      if (!str_starts_with($key, 'help_center_item_')) {
        continue;
      }
      $query = $storage->getQuery();
      $query->accessCheck(TRUE);
      $query->condition('vid', 'vactory_help_center');
      $query->condition('term_2_slug', $item);
      $query->condition('parent', $current_term);
      $result = $query->execute();
      if (!empty($result) && count($result) == 1) {
        $current_term = reset($result);
      }
    }
    if ($current_term == 0) {
      return NULL;
    }
    $term = $storage->load($current_term);
    return $this->entityRepository->getTranslationFromContext($term, $langcode)->label();
  }

}
