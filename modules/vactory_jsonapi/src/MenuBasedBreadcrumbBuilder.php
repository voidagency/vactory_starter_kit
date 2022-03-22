<?php

namespace Drupal\vactory_jsonapi;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * {@inheritdoc}
 */
class MenuBasedBreadcrumbBuilder {

  use StringTranslationTrait;

  /**
   * The menu active trail interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The menu where the current page or taxonomy match has taken place.
   *
   * @var string
   */
  private $menuName = 'main';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The lock backend that should be used.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The entity repository manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The menu_link_content storage handler.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentStorageInterface
   */
  protected $menuLinkContentStorage;

  /**
   * The inbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The router doing the actual routing.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    TranslationInterface $string_translation,
    MenuLinkManagerInterface $menu_link_manager,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactory $config,
    CacheBackendInterface $cache_backend,
    LockBackendInterface $lock,
    EntityRepositoryInterface $entity_repository,
    InboundPathProcessorInterface $path_processor,
    RequestMatcherInterface $router,
    TitleResolverInterface $title_resolver,
    RouteMatchInterface $route_match
  ) {
    $this->stringTranslation = $string_translation;
    $this->menuLinkManager = $menu_link_manager;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config;
    $this->cacheBackend = $cache_backend;
    $this->lock = $lock;
    $this->entityRepository = $entity_repository;
    $this->pathProcessor = $path_processor;
    $this->router = $router;
    $this->titleResolver = $title_resolver;
    $this->routeMatch = $route_match;
    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match, $langcode) {
    // Fake menu active trail.
    $this->menuActiveTrail = new MenuActiveTrail(
      $this->menuLinkManager,
      $route_match,
      $this->cacheBackend,
      $this->lock
    );

    $language = $this->languageManager->getLanguage($langcode);
    $breadcrumb = new Breadcrumb();
//    $breadcrumb->addCacheContexts(['languages:language_content']);
//    $breadcrumb->addCacheContexts(['url.path']);

    // Add home.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home', [], ['langcode' => $langcode])
      ->render(), '<front>', [], [
      'language' => $language,
    ]));

    // Look for links from menu.
    $menuLinks = $this->getMenuLinks($langcode);
    foreach ($menuLinks as $link) {
      $breadcrumb->addLink($link);
    }

    // Look for links from path.
    if (empty($menuLinks)) {
      $path = $route_match->getCurrentRequest()->getPathInfo();
      $pathLinks = $this->getPathLinks($path, $langcode);
      foreach ($pathLinks as $link) {
        $breadcrumb->addLink($link);
      }
    }

    return $breadcrumb;
  }

  /**
   * {@inheritdoc}
   */
  private function getMenuLinks($langcode) {
    $links = [];
    $language = $this->languageManager->getLanguage($langcode);
    $trail_ids = $this->menuActiveTrail->getActiveTrailIds($this->menuName);
    $trail_ids = array_filter($trail_ids);

    if (!empty($trail_ids)) {
      foreach (array_reverse($trail_ids) as $id) {
        $plugin = $this->menuLinkManager->createInstance($id);
        $definition = $plugin->getPluginDefinition();
        $entity_id = $definition['metadata']['entity_id'];
        /* @var \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent $menuLink */
        $menuLink = $this->menuLinkContentStorage->load($entity_id);
        $menuLink = $this->entityRepository->getTranslationFromContext($menuLink, $langcode);
        /* @var \Drupal\Core\Url $link */
        $link = $menuLink->getUrlObject();
        $link->setOption('language', $language);
        // @todo: homepage link > compare with site settings node and remove /hompage.

        $links[] = Link::fromTextAndUrl($menuLink->label(), $link);
      }
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  private function getPathLinks($path = '', $langcode) {
    $links = [];
    $path = urldecode($path);
    $path_elements = explode('/', $path);
    // Remove first slash.
    array_shift($path_elements);
    // Remove language prefix.
    $curr_lang = $path_elements[0];
    array_shift($path_elements);

    if (isset($path_elements[0])) {
      while (count($path_elements) > 0) {
        $check_path = '/' . implode('/', $path_elements);
        $check_path = '/' . $curr_lang . $check_path;

        $route_request = $this->getRequestForPath($check_path);

        if ($route_request) {
          $route_match = RouteMatch::createFromRequest($route_request);
          $title = $this->getTitleString($route_request, $route_match);
          $url = Url::fromRouteMatch($route_match);
          $links[] = Link::fromTextAndUrl($title, $url);
        }

        array_pop($path_elements);
      }

      $links = array_reverse($links);
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  private function getRequestForPath($path = '') {
    $request = Request::create($path);
    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');

    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor
      ->processInbound($path, $request);
    if (empty($processed)) {
      // This resolves to the front page, which we already add.
      return NULL;
    }

    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add($this->router->matchRequest($request));
      return $request;
    } catch (ResourceNotFoundException $e) {
      return NULL;
    } catch (MethodNotAllowedException $e) {
      return NULL;
    } catch (AccessDeniedHttpException $e) {
      return NULL;
    }

  }

  /**
   * Get string title for route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $route_request
   *   A request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return string|null
   *   Either the current title string or NULL if unable to determine it.
   */
  private function getTitleString(Request $route_request, RouteMatchInterface $route_match) {
    // @todo: IOC
    $title = $this->titleResolver
      ->getTitle($route_request, $route_match->getRouteObject());

    // Get string from title. Different routes return different objects.
    // Many routes return a translatable markup object.
    if ($title instanceof TranslatableMarkup) {
      $title = $title->render();
    }
    elseif ($title instanceof FormattableMarkup) {
      $title = (string) $title;
    }

    // Other paths, such as admin/structure/menu/manage/main, will
    // return a render array suitable to render using core's XSS filter.
    elseif (is_array($title) && array_key_exists('#markup', $title)) {

      // If this render array has #allowed tags use that instead of default.
      $tags = array_key_exists('#allowed_tags', $title) ? $title['#allowed_tags'] : NULL;
      $title = Xss::filter($title['#markup'], $tags);
    }

    // If a route declares the title in an unexpected way, log and return NULL.
    if (!is_string($title)) {
      return NULL;
    }

    return $title;
  }

}
