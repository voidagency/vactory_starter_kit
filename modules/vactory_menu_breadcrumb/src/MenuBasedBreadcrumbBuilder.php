<?php

namespace Drupal\vactory_menu_breadcrumb;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * {@inheritdoc}
 */
class MenuBasedBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The dynamic router service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * The admin context generator.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

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
   * The Menu Breadcrumbs configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The menu where the current page or taxonomy match has taken place.
   *
   * @var string
   */
  private $menuName;

  /**
   * The menu trail leading to this match.
   *
   * @var array
   */
  private $menuTrail;

  /**
   * Node of current path if taxonomy attached.
   *
   * @var \Drupal\node\Entity\Node
   */
  private $taxonomyAttachment;

  /**
   * Content language code (used in both applies() and build()).
   *
   * @var string
   */
  private $contentLanguage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RequestContext $context,
    AccessManagerInterface $access_manager,
    RequestMatcherInterface $router,
    InboundPathProcessorInterface $path_processor,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    MenuActiveTrailInterface $menu_active_trail,
    MenuLinkManagerInterface $menu_link_manager,
    AdminContext $admin_context,
    TitleResolverInterface $title_resolver,
    RequestStack $request_stack,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->context = $context;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuLinkManager = $menu_link_manager;
    $this->adminContext = $admin_context;
    $this->titleResolver = $title_resolver;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $this->configFactory->get('vactory_menu_breadcrumb.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // This may look heavyweight for applies() but we have to check all ways the
    // current path could be attached to the selected menus before turning over
    // breadcrumb building (and caching) to another builder.  Generally this
    // should not be a problem since it will then fall back to the system (path
    // based) breadcrumb builder which caches a breadcrumb no matter what.
    if (!$this->config->get('determine_menu')) {
      return FALSE;
    }
    // Don't breadcrumb the admin pages, if disabled on config options:
    if ($this->config->get('disable_admin_page') && $this->adminContext->isAdminRoute($route_match->getRouteObject())) {
      return FALSE;
    }
    // No route name means no active trail:
    $route_name = $route_match->getRouteName();
    if (!$route_name) {
      return FALSE;
    }

    // This might be a "node" with no fields, e.g. a route to a "revision" URL,
    // so we don't check for taxonomy fields on unfieldable nodes:
    $node_object = $route_match->getParameters()->get('node');
    $node_is_fieldable = $node_object instanceof FieldableEntityInterface;

    // Make sure menus are selected, and breadcrumb text strings, are displayed
    // in the content rather than the (default) interface language:
    $this->contentLanguage = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    // Check each selected menu, in turn, until a menu or taxonomy match found:
    // then cache its state for building & caching in build() and exit.
    $menus = $this->config->get('vactory_menu_breadcrumb_menus');
    uasort($menus, function ($a, $b) {
      return SortArray::sortByWeightElement($a, $b);
    });
    foreach ($menus as $menu_name => $params) {

      // Look for current path on any enabled menu.
      if (!empty($params['enabled'])) {

        // Skip over any menu that's not in the current content language,
        // if and only if the "language handling" option set for that menu.
        // NOTE this menu option is added late, so we check its existence first.
        if (array_key_exists('langhandle', $params) && $params['langhandle']) {
          $menu_objects = $this->entityTypeManager->getStorage('menu')
            ->loadByProperties(['id' => $menu_name]);
          if ($menu_objects) {
            $menu_language = reset($menu_objects)->language()->getId();
            if ($menu_language != $this->contentLanguage &&
              $menu_language !== Language::LANGCODE_NOT_SPECIFIED &&
              $menu_language !== Language::LANGCODE_NOT_APPLICABLE) {
              continue;
            }
          }
        }

        $trail_ids = $this->menuActiveTrail->getActiveTrailIds($menu_name);
        $trail_ids = array_filter($trail_ids);
        if($node_object && !$trail_ids){
          $nodeObj = entity_load('node',$node_object->get('nid')->value);
          $bundle = $nodeObj->bundle();
          $content_type = \Drupal\node\Entity\NodeType::load($bundle);
          $parent_menu = $content_type->getThirdPartySetting('menu_ui', 'parent', '');
          if($parent_menu){
            $parent_menu_infos = explode(':',$parent_menu, 2)[1];
            $parentIds = $this->menuLinkManager->getParentIds($parent_menu_infos);
          }
          else
          $parentIds = $this->menuLinkManager->getParentIds($parent_menu);
          $this->menuName = $menu_name;
          $this->menuTrail = $parentIds;
          $this->taxonomyAttachment = NULL;
          return TRUE;
        }
        $this->menuName = $menu_name;
        $this->menuTrail = $trail_ids;
        $this->taxonomyAttachment = NULL;
        return TRUE;
      }

      // Look for a "taxonomy attachment" by node field, regardless of language.
      if (!empty($params['taxattach']) && $node_is_fieldable) {

        // Check all taxonomy terms applying to the current page.
        foreach ($node_object->getFields() as $field) {
          if ($field->getSetting('target_type') == 'taxonomy_term') {

            // In general these entity references will support multiple
            // values so we check all terms in the order they are listed.
            foreach ($field->referencedEntities() as $term) {
              $url = $term->toUrl();
              $route_links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu_name);
              if (!empty($route_links)) {
                // Successfully found taxonomy attachment, so pass to build():
                // - the menu in in which we have found the attachment
                // - the effective menu trail of the taxonomy-attached node
                // - the node itself (in build() we will find its title & URL)
                $taxonomy_term_link = reset($route_links);
                $taxonomy_term_id = $taxonomy_term_link->getPluginId();
                $this->menuName = $menu_name;
                $this->menuTrail = $this->menuLinkManager->getParentIds($taxonomy_term_id);
                $this->taxonomyAttachment = $node_object;
                return TRUE;
              }
            }
          }
        }
      }
    }
    // No more menus to check...
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    if (!empty($this->menuTrail)) {
      $breadcrumb = new Breadcrumb();
      // Breadcrumbs accumulate in this array, with lowest index being the root
      // (i.e., the reverse of the assigned breadcrumb trail):
      $links = [];

      if ($this->languageManager->isMultilingual()) {
        $breadcrumb->addCacheContexts(['languages:language_content']);
      }

      // Changing the <front> page will invalidate,
      // any breadcrumb generated here.
      $site_config = $this->configFactory->get('system.site');
      $breadcrumb->addCacheableDependency($site_config);
      $breadcrumb->addCacheContexts(['url.path']);

      // Changing any module settings will invalidate the breadcrumb:
      $breadcrumb->addCacheableDependency($this->config);

      // Changing the active trail of the current path,
      // or taxonomy-attached path,
      // on this menu will invalidate this breadcrumb:
      $breadcrumb->addCacheContexts(['route.menu_active_trails:' . $this->menuName]);

      // Generate basic breadcrumb trail from active trail.
      // Keep same link ordering as Menu Breadcrumb,
      // (so also reverses menu trail)
      if (!empty($this->menuTrail)) {
        foreach (array_reverse($this->menuTrail) as $id) {
          $plugin = $this->menuLinkManager->createInstance($id);
          // In the last line,
          // the MenuLinkContent plugin is not providing cache tags.
          // Until this is fixed in core add the tags here:
          if ($plugin instanceof MenuLinkContent) {
            $uuid = $plugin->getDerivativeId();
            $entities = $this->entityTypeManager->getStorage('menu_link_content')
              ->loadByProperties(['uuid' => $uuid]);
            if (array_keys($entities)[0] == 2) {
               continue;
            }
            if ($entity = reset($entities)) {
              $breadcrumb->addCacheableDependency($entity);
            }
          }
          $links[] = Link::fromTextAndUrl($plugin->getTitle(), $plugin->getUrlObject());
          $breadcrumb->addCacheableDependency($plugin);
        }
      }

      $this->addMissingCurrentPage($links, $route_match);
      // Create a breadcrumb for <front> which may be either added or replaced:
      $langcode = $this->contentLanguage;
      $label = $this->config->get('home_as_site_name') ?
        $this->configFactory->get('system.site')->get('name') :
        $this->t('Home', [], ['langcode' => $langcode]);
      $home_link = Link::createFromRoute($label, '<front>');
      // Add Front page.
      array_unshift($links, $home_link);
      // The first link from the menu trail, being the root, may be the
      // <front> so first compare those two routes to see if they are identical.
      // (Though in general a link deeper in the menu could be <front>, in that
      // case it's arguable that the node-based pathname would be preferred.)
      $front_page = $site_config->get('page.front');
      $front_url = Url::fromUri("internal:$front_page");
      $first_url = $links[0]->getUrl();
      // If options are set to remove <front>, strip off that link, otherwise
      // replace it with a breadcrumb named according to option settings:
      if (($first_url->isRouted() && $front_url->isRouted()) &&
        ($front_url->getRouteName() === $first_url->getRouteName()) &&
        ($front_url->getRouteParameters() === $first_url->getRouteParameters())) {

        // According to the confusion hopefully cleared up in issue 2754521, the
        // sense of "remove_home" is slightly different than in Menu Breadcrumb:
        // we remove any match with <front> rather than replacing it.
        if ($this->config->get('remove_home')) {
          array_shift($links);
        }
        else {
          $links[0] = $home_link;
        }
      }
      else {
        // If trail *doesn't* begin with the home page,
        // add it if that option set.
        if ($this->config->get('add_home')) {
          array_unshift($links, $home_link);
        }
      }
      if (!empty($links)) {
        $page_type = $this->taxonomyAttachment ? 'member_page' : 'current_page';
        // Display the last item of the breadcrumbs trail,
        // as the options indicate.
        /** @var \Drupal\Core\Link $current */
        $current = array_pop($links);
        if ($this->config->get('append_' . $page_type)) {
          if (!$this->config->get($page_type . '_as_link')) {
            $current->setUrl(new Url('<none>'));
          }
          array_push($links, $current);
        }
      }
      return $breadcrumb->setLinks($links);
    }
    else {
      $linkTree = \Drupal::getContainer()->get('menu.link_tree');
      $breadcrumb = new Breadcrumb();
      $links = [];
      $exclude = [];
      $curr_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

      // General path-based breadcrumbs. Use the actual request path, prior to
      // resolving path aliases, so the breadcrumb can be defined by simply
      // creating a hierarchy of path aliases.
      $path = trim($this->context->getPathInfo(), '/');
      $path = urldecode($path);
      $path_elements = explode('/', $path);
      $exclude['/user'] = TRUE;

      // Because this breadcrumb builder is path and config based, vary cache
      // by the 'url.path' cache context and config changes.
      $breadcrumb->addCacheContexts(['url.path']);
      $breadcrumb->addCacheableDependency($this->config);
      $i = 0;

      // Remove the current page if it's not wanted.
      if (!$this->config->get(VactoryBreadcrumbConstants::INCLUDE_TITLE_SEGMENT)) {
        array_pop($path_elements);
      }

      if (isset($path_elements[0])) {

        // Remove the first parameter if it matches the current language.
        if (!($this->config->get(VactoryBreadcrumbConstants::LANGUAGE_PATH_PREFIX_AS_SEGMENT))) {
          if (mb_strtolower($path_elements[0]) == $curr_lang) {
            array_shift($path_elements);
          }
        }
      }
      while (count($path_elements) > 0) {
        // Copy the path elements for up-casting.
        $route_request = $this->getRequestForPath('/' . implode('/', $path_elements), $exclude);
        if ($this->config->get(VactoryBreadcrumbConstants::EXCLUDED_PATHS)) {
          $config_textarea = $this->config->get(VactoryBreadcrumbConstants::EXCLUDED_PATHS);
          $excludes = preg_split('/[\r\n]+/', $config_textarea, -1, PREG_SPLIT_NO_EMPTY);
          if (in_array(end($path_elements), $excludes)) {
            break;
          }
        }
        if ($route_request) {
          $route_match = RouteMatch::createFromRequest($route_request);
          $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
          // The set of breadcrumb links depends on the access result, so merge
          // the access result's cacheability metadata.
          if ($access->isAllowed()) {
            $title = str_replace(['-', '_'], ' ', $path_elements[count($path_elements) - 1]);
            if ($this->config->get(VactoryBreadcrumbConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE)) {
              if ($this->titleResolver->getTitle($route_request, $route_match->getRouteObject())) {
                $title = $this->titleResolver->getTitle($route_request, $route_match->getRouteObject());
              }
            }
            // Add a linked breadcrumb unless it's the current page.
            if ($i == 0
              && $this->config->get(VactoryBreadcrumbConstants::INCLUDE_TITLE_SEGMENT)
              && !$this->config->get(VactoryBreadcrumbConstants::TITLE_SEGMENT_AS_LINK)) {
              $links[] = Link::createFromRoute($title, '<none>');
            }
            else {
              $url = Url::fromRouteMatch($route_match);
              $item_title = '';
              $output = $linkTree->load($this->menuName, new MenuTreeParameters());
              foreach ($output as $item) {
                $item_title = $this->testUrlsItem($item, $url->toString());
                if ($item_title !== '') {
                  break;
                }
              }
              $title = ($item_title) ? $item_title : $title;
              $links[] = new Link($title, $url);
            }
            unset($title);
            $i++;
          }
        }
        elseif ($this->config->get(VactoryBreadcrumbConstants::INCLUDE_INVALID_PATHS)) {
          // TODO: exclude the 404 page and other's with a system path.
          $title = str_replace(['-', '_'], ' ', Unicode::ucfirst(end($path_elements)));
          $links[] = Link::createFromRoute($title, '<none>');
        }
        array_pop($path_elements);
      }
      // Add the home link, if desired.
      if ($this->config->get(VactoryBreadcrumbConstants::INCLUDE_HOME_SEGMENT)) {
        if ($path && '/' . $path != $curr_lang) {
          $links[] = Link::createFromRoute($this->config->get(VactoryBreadcrumbConstants::HOME_SEGMENT_TITLE), '<front>');
        }
      }
      $links = array_reverse($links);

      if ($this->config->get(VactoryBreadcrumbConstants::REMOVE_REPEATED_SEGMENTS)) {
        $links = $this->removeRepeatedSegments($links);
      }
      return $breadcrumb->setLinks($links);
    }
  }

  /**
   * If the current page is missing from the breadcrumb links, add it.
   *
   * @param \Drupal\Core\Link[] $links
   *   The breadcrumb links.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  protected function addMissingCurrentPage(array &$links, RouteMatchInterface $route_match) {
    // Check if the current page is already present.
    /*if (!empty($links)) {
    $last_url = end($links)->getUrl();
    if ($last_url->isRouted() &&
    $last_url->getRouteName() === $route_match->getRouteName() &&
    $last_url->getRouteParameters() === $route_match->getRawParameters()->all()
    ) {
    // We already have a link, no need to add one.
    return;
    }
    }*/

    // If we got here, the current page is missing from the breadcrumb links.
    // This can happen if the active trail is only partial, and doesn't reach
    // the current page, or if a taxonomy attachment is used.
    $title = $this->titleResolver->getTitle($this->currentRequest,
      $route_match->getRouteObject());
    if (isset($title)) {
      $links[] = Link::fromTextAndUrl($title,
        Url::fromRouteMatch($route_match));
    }
  }

  /**
   * Remove duplicate repeated segments.
   *
   * @param \Drupal\Core\Link[] $links
   *   The links.
   *
   * @return \Drupal\Core\Link[]
   *   The new links.
   */
  protected function removeRepeatedSegments(array $links) {
    $newLinks = [];

    /** @var \Drupal\Core\Link $last */
    $last = NULL;

    foreach ($links as $link) {
      if (empty($last) || (!$this->linksAreEqual($last, $link))) {
        $newLinks[] = $link;
      }

      $last = $link;
    }

    return $newLinks;
  }

  /**
   * Compares two breadcrumb links for equality.
   *
   * @param \Drupal\Core\Link $link1
   *   The first link.
   * @param \Drupal\Core\Link $link2
   *   The second link.
   *
   * @return bool
   *   TRUE if equal, FALSE otherwise.
   */
  protected function linksAreEqual(Link $link1, Link $link2) {
    $links_equal = TRUE;

    if ($link1->getText() != $link2->getText()) {
      $links_equal = FALSE;
    }

    if ($link1->getUrl()->getInternalPath() != $link2->getUrl()->getInternalPath()) {
      $links_equal = FALSE;
    }

    return $links_equal;
  }

  /**
   * Matches a path in the router.
   *
   * @param string $path
   *   The request path with a leading slash.
   * @param array $exclude
   *   An array of paths or system paths to skip.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A populated request object or NULL if the path couldn't be matched.
   */
  protected function getRequestForPath($path, array $exclude) {
    if (!empty($exclude[$path])) {
      return NULL;
    }
    // @todo Use the RequestHelper once https://www.drupal.org/node/2090293 is
    //   fixed.
    $request = Request::create($path);

    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($path, $request);
    if (empty($processed) || !empty($exclude[$processed])) {
      // This resolves to the front page, which we already add.
      return NULL;
    }
    $this->currentPath->setPath($processed, $request);
    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add($this->router->matchRequest($request));
      return $request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
    catch (MethodNotAllowedException $e) {
      return NULL;
    }
    catch (AccessDeniedHttpException $e) {
      return NULL;
    }
  }

/**
   * Test correspondence between url and menu item link.
   *
   * @param Drupal\Core\Menu\MenuLinkTreeElement $item
   *   An MenuLinkTreeElement item.
   * @param string $url
   *   A searched url.
   *
   * @return string
   *   A menu item title.
   */
  public function testUrlsItem(MenuLinkTreeElement $item, $url) {
     $title = '';
      if ($item->link->getRouteName() !== '') {
          $item_url = (new Url($item->link->getRouteName(), $item->link->getRouteParameters(), ['set_active_class' => TRUE]))->toString();
        }
   else {
          $item_url = '';
        }
    if ($item_url == $url) {
          return $item->link->getTitle();
    }
    elseif ($item->subtree) {
       foreach ($item->subtree as $child) {
            $title = $title . $this->testUrlsItem($child, $url);
          }
      return $title;
    }
    else {
          return '';
    }
  }

}
