<?php

namespace Drupal\vactory_decoupled_router;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Site\Settings;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rules of the router: page entity types, canonical paths, alias variants.
 */
class PageRules {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The inbound path processor of the router.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface|null
   */
  protected $pathProcessor;

  /**
   * Constructs the service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    AliasManagerInterface $alias_manager,
    TransliterationInterface $transliteration,
    LanguageManagerInterface $language_manager,
    ?InboundPathProcessorInterface $path_processor = NULL,
  ) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->aliasManager = $alias_manager;
    $this->transliteration = $transliteration;
    $this->languageManager = $language_manager;
    $this->pathProcessor = $path_processor;
  }

  /**
   * The entity types the router resolves as pages.
   *
   * In this order: the "page_entity_types" key of the
   * vactory_decoupled_router.settings config (node by default), replaced by
   * $settings['vactory_decoupled_router_page_entity_types'] of settings.php
   * when it is defined, then altered by
   * hook_vactory_decoupled_router_page_entity_types_alter().
   *
   * @return string[]
   *   The entity type IDs.
   */
  public function getPageEntityTypes(): array {
    $entity_types = $this->configFactory->get('vactory_decoupled_router.settings')->get('page_entity_types') ?? ['node'];
    $entity_types = Settings::get('vactory_decoupled_router_page_entity_types', $entity_types);
    $this->moduleHandler->alter('vactory_decoupled_router_page_entity_types', $entity_types);
    return array_values(array_filter((array) $entity_types, 'is_string'));
  }

  /**
   * Whether the entities of a type are resolved as pages.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   */
  public function isPageEntityType(string $entity_type_id): bool {
    return in_array($entity_type_id, $this->getPageEntityTypes(), TRUE);
  }

  /**
   * Whether a value of "path" can be a path of the site.
   *
   * A value starting with two slashes is read as a URL without scheme: its
   * first segment is taken as a host name and "//segment" would be the front
   * page.
   *
   * @param string $path
   *   The requested path.
   */
  public function isSitePath(string $path): bool {
    return !str_starts_with($path, '//');
  }

  /**
   * Whether a path is the canonical address of an entity.
   *
   * The path is processed as the router does (language prefix, alias, front
   * page) and has to give exactly the internal path of the canonical URL of
   * the entity. The other routes of the entity (edit and delete forms,
   * revisions, latest version, translations, JSON:API...) and the other
   * spellings of its internal path (letter case, leading zero, trailing slash)
   * are not its address.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of the route matched for the path.
   * @param string $path
   *   The requested path.
   */
  public function isCanonicalPath(EntityInterface $entity, string $path): bool {
    try {
      $internal = '/' . $entity->toUrl('canonical')->getInternalPath();
      $request = Request::create($path);
      $path_info = $request->getPathInfo();
      // Same trim as the route provider of the core.
      $trimmed = $path_info === '/' ? $path_info : rtrim($path_info, '/');
      $processed = $this->getPathProcessor()->processInbound($trimmed, $request);
    }
    catch (\Exception $exception) {
      // No canonical URL for this entity, or a path that cannot be processed.
      return FALSE;
    }
    $requested = $this->getRequestedAlias($path);
    $has_trailing_slash = $requested !== $internal && rtrim($requested, '/') === $internal;
    return $processed === $internal && !$has_trailing_slash;
  }

  /**
   * The inbound path processor of the router.
   *
   * Read from the container when the service was built by a container older
   * than this argument (code deployed, cache not rebuilt yet).
   */
  protected function getPathProcessor(): InboundPathProcessorInterface {
    if (!$this->pathProcessor) {
      $this->pathProcessor = \Drupal::service('path_processor_manager');
    }
    return $this->pathProcessor;
  }

  /**
   * The target of the redirect when a path is a variant of a node alias.
   *
   * The alias lookup of the database ignores the letter case (and the accents
   * with the usual collations): each variant would be another address of the
   * same page. A variant is the alias written with another letter case, other
   * accents or a trailing slash. The stored alias itself is never a variant,
   * with or without a language prefix, so that the target of the redirect is
   * not redirected again.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node the path was resolved to.
   * @param string $path
   *   The requested path.
   * @param string $langcode
   *   The language of the request.
   *
   * @return string|null
   *   The URL of the alias in the language of the request (whatever the
   *   language of the node: the visitor stays in the same language), or NULL
   *   when the path is not a variant.
   */
  public function getAliasRedirectTarget(EntityInterface $node, string $path, string $langcode): ?string {
    $system_path = '/node/' . $node->id();
    $alias = $this->aliasManager->getAliasByPath($system_path, $langcode);
    $requested = $this->getRequestedAlias($path);
    $is_variant = $alias !== $system_path
      && $requested !== $alias
      && $this->foldAlias($requested) === $this->foldAlias($alias);
    if (!$is_variant) {
      return NULL;
    }
    $options = ['language' => $this->languageManager->getLanguage($langcode)];
    return $node->toUrl('canonical', $options)->toString();
  }

  /**
   * The requested path, as the alias lookup of the router received it.
   *
   * Decoded, with a leading slash, without query string, fragment or language
   * prefix.
   */
  protected function getRequestedAlias(string $path): string {
    $requested = rawurldecode(Request::create($path)->getPathInfo());
    $negotiation = $this->configFactory->get('language.negotiation');
    $parts = explode('/', ltrim($requested, '/'), 2);
    $has_prefix = $negotiation->get('url.source') === 'path_prefix'
      && $parts[0] !== ''
      && in_array($parts[0], (array) $negotiation->get('url.prefixes'), TRUE);
    return $has_prefix ? '/' . ($parts[1] ?? '') : $requested;
  }

  /**
   * An alias without letter case, accents and trailing slash, to compare.
   */
  protected function foldAlias(string $alias): string {
    return mb_strtolower($this->transliteration->removeDiacritics(rtrim($alias, '/')));
  }

}
