<?php

namespace Drupal\vactory_decoupled_router\Controller;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Symfony\Component\Routing\RouteCollection;
use Drupal\redirect\RedirectRepository;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\vactory_decoupled\BlocksManager;

/**
 * Controller that receives the path to inspect.
 */
class PathTranslator extends ControllerBase {

  /**
   * The response.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  private $response;

  /**
   * JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  private $jsonapiResourceTypeRepository;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * System routes.
   *
   * @var array|\Drupal\Core\Entity\EntityInterface[]
   */
  protected $systemRoutes = [];

  /**
   * Current langcode.
   *
   * @var string
   */
  protected $currentLangcode;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;
  /**
   * The router service.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $router;

  /**
   * The redirect repository service.
   *
   * @var \Drupal\redirect\RedirectRepository
   */
  protected $redirectRepository;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface|null
   */
  protected $aliasManager;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface|null
   */
  protected $transliteration;

  /**
   * EventInfoController constructor.
   */
  public function __construct(
    LoggerInterface $logger,
    UrlMatcherInterface $router,
    ResourceTypeRepository $jsonapiResourceTypeRepository,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    RedirectRepository $redirectRepository,
    ?AliasManagerInterface $aliasManager = NULL,
    ?TransliterationInterface $transliteration = NULL,
  ) {
    $this->logger = $logger;
    $this->router = $router;
    $this->jsonapiResourceTypeRepository = $jsonapiResourceTypeRepository;
    $this->entityTypeManager = $entityTypeManager;
    $this->systemRoutes = $this->entityTypeManager->getStorage('vactory_route')
      ->loadMultiple();
    $this->currentLangcode = $this->languageManager()
      ->getCurrentLanguage()
      ->getId();
    $this->entityRepository = $entityRepository;
    $this->redirectRepository = $redirectRepository;
    $this->aliasManager = $aliasManager;
    $this->transliteration = $transliteration;
  }

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.vactory_decoupled_router'),
      $container->get('router.no_access_checks'),
      $container->get('jsonapi.resource_type.repository'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('redirect.repository'),
      $container->get('path_alias.manager'),
      $container->get('transliteration'),
    );
  }

  /**
   * Responds with all the information about the path.
   */
  public function translate(Request $request) {
    $path = $this->getPathFromRequest($request);

    if (!isset($this->systemRoutes['error_page'])) {
      $this->logger->error('System route error_page is not found. Create one at /admin/config/system/vactory_router');
      return;
    }

    /** @var \Drupal\vactory_decoupled_router\Entity\Route $error_route */
    $error_route = $this->systemRoutes['error_page'];
    $error_match_info = $this->router->match($error_route->getPath());

    // Assume a 200 from start.
    $this->response = new JsonResponse([], 200);

    $output = [];
    $output['status'] = 200;

    // Honor redirects created via the core Redirect module BEFORE any route
    // matching. The redirect source (e.g. an old URL) usually has no Drupal
    // route, so $this->router->match() would throw and fall through to the 404
    // error_page. Resolving the redirect first lets editor-managed 301s win.
    $redirect = $this->redirectRepository->findMatchingRedirect(
      $path,
      [],
      $this->currentLangcode
    );
    $output = $redirect
      ? $this->redirectOutput($redirect, $path, $output)
      : $this->resolveOutput($request, $path, $output, $error_match_info);
    if ($output === NULL) {
      return;
    }

    $this->response->headers->add(['Content-Type' => 'application/json']);
    $this->response->setData($output);

    return $this->response;
  }

  /**
   * Output of a path handled by a Redirect entity.
   */
  private function redirectOutput($redirect, string $path, array $output): array {
    $status_code = $redirect->getStatusCode();
    $output['status'] = $status_code;
    $output['redirect'][] = [
      'from' => $path,
      'to' => $redirect->getRedirectUrl()->toString(),
      'status' => $status_code,
    ];
    return $output;
  }

  /**
   * Output of a path resolved to an entity, an error page or a login redirect.
   *
   * @return array|null
   *   The output, or NULL when the account_login system route is missing.
   */
  private function resolveOutput(Request $request, string $path, array $output, array $error_match_info): ?array {
    $info = NULL;
    $match_info = $this->matchPath($request, $path, $output, $error_match_info, $info);
    $preview = $request->query->get('preview') ?? FALSE;
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->findPageEntity($match_info, $preview, $path, $output);
    if (!$entity) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->findEntity($error_match_info);
      $info = [
        '_route' => 'error_page',
      ];
      $output['status'] = 404;
    }

    $redirects_trace = [];
    if (!$this->applyViewAccess($entity, $path, $output, $error_match_info, $info, $redirects_trace)) {
      return NULL;
    }

    $output = array_merge($this->getEntityOutput($entity, $output['status']), $output);

    if ($info) {
      $output['system'] = $info;
    }

    if (
      $this->isFollowNodePathPattern($path)
      && $entity->getEntityTypeId() === 'node'
      && $entity->hasTranslation($this->currentLangcode)
    ) {
      // Get the translated entity.
      $trans_entity = $this->entityRepository->getTranslationFromContext($entity);

      // Generate the alias URL string.
      $alias = $trans_entity->toUrl()->toString();
      // Check if the alias exists and does not follow the node path pattern.
      if (!empty($alias) && !$this->isFollowNodePathPattern($alias)) {
        $redirects_trace[] = [
          'to' => $alias,
          'status' => 301,
          'from' => $path,
        ];
        $output['redirect'] = $redirects_trace;
      }
    }

    // Vactory system routes ($info set) are skipped: their paths (facets...)
    // share the node of the route.
    if ($info === NULL) {
      $output = $this->addAliasVariantRedirect($entity, $path, $output);
    }

    return $output;
  }

  /**
   * The published entity of the matched route, when it is a page.
   *
   * The entity type is checked before the entity itself is inspected: a type
   * out of the list of page entity types is never loaded further.
   *
   * @param array $match_info
   *   The router match info.
   * @param bool|string $preview
   *   Is preview mode.
   * @param string $path
   *   The requested path.
   * @param array $output
   *   The output; receives the message when there is no page.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity, or NULL when the error page has to be returned.
   */
  private function findPageEntity(array $match_info, $preview, string $path, array &$output): ?EntityInterface {
    $candidate = $this->getRouteEntity($match_info);
    // A path that already gave the error page keeps its own message.
    $refused = $output['status'] === 200
      && $candidate
      && !in_array($candidate->getEntityTypeId(), $this->getPageEntityTypes(), TRUE);
    if ($refused) {
      // Not logged: such paths are requested in number by crawlers.
      $output['message'] = "No decoupled page for $path";
      return NULL;
    }
    $entity = $this->findEntity($match_info, $preview);
    if (!$entity) {
      $this->logger->notice('A route has been found but it has no entity information.');
      $output['message'] = "A route has been found for $path , but it has no entity information!";
      // $this->response->setStatusCode(500);
    }
    return $entity;
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
  private function getPageEntityTypes(): array {
    $entity_types = $this->config('vactory_decoupled_router.settings')->get('page_entity_types') ?? ['node'];
    $entity_types = Settings::get('vactory_decoupled_router_page_entity_types', $entity_types);
    $this->moduleHandler()->alter('vactory_decoupled_router_page_entity_types', $entity_types);
    return array_values(array_filter((array) $entity_types, 'is_string'));
  }

  /**
   * Adds a permanent redirect when the path is a variant of the node alias.
   *
   * The alias lookup of the database ignores the letter case (and the accents
   * with the usual collations): each variant would be another address of the
   * same page. The redirect status is the string '301', like the one of a
   * Redirect entity: this is what the front reads as permanent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The resolved entity.
   * @param string $path
   *   The requested path.
   * @param array $output
   *   The output.
   *
   * @return array
   *   The output, with the redirect when there is one.
   */
  private function addAliasVariantRedirect(EntityInterface $entity, string $path, array $output): array {
    $is_candidate = $output['status'] === 200
      && empty($output['redirect'])
      && $entity->getEntityTypeId() === 'node';
    if ($is_candidate && $this->isAliasVariant($entity, $path)) {
      // The alias of the language of the request, whatever the language of
      // the node: the visitor stays in the same language.
      $options = ['language' => $this->languageManager()->getCurrentLanguage()];
      $output['status'] = 301;
      $output['redirect'][] = [
        'to' => $entity->toUrl('canonical', $options)->toString(),
        'status' => '301',
        'from' => $path,
      ];
    }
    return $output;
  }

  /**
   * Whether the path is the alias of the node written in another way.
   *
   * Another letter case, other accents or a trailing slash. The stored alias
   * itself is never a variant, with or without a language prefix, so that the
   * target of the redirect is not redirected again.
   */
  private function isAliasVariant(EntityInterface $node, string $path): bool {
    $system_path = '/node/' . $node->id();
    $alias = $this->getAliasManager()->getAliasByPath($system_path, $this->currentLangcode);
    $requested = $this->getRequestedAlias($path);
    return $alias !== $system_path
      && $requested !== $alias
      && $this->foldAlias($requested) === $this->foldAlias($alias);
  }

  /**
   * The requested path, as the alias lookup of the router received it.
   *
   * Decoded, with a leading slash, without query string, fragment or language
   * prefix.
   */
  private function getRequestedAlias(string $path): string {
    $requested = rawurldecode(Request::create($path)->getPathInfo());
    $negotiation = $this->config('language.negotiation');
    $parts = explode('/', ltrim($requested, '/'), 2);
    $has_prefix = $negotiation->get('url.source') === 'path_prefix'
      && $parts[0] !== ''
      && in_array($parts[0], (array) $negotiation->get('url.prefixes'), TRUE);
    return $has_prefix ? '/' . ($parts[1] ?? '') : $requested;
  }

  /**
   * An alias without letter case, accents and trailing slash, to compare.
   */
  private function foldAlias(string $alias): string {
    return mb_strtolower($this->getTransliteration()->removeDiacritics(rtrim($alias, '/')));
  }

  /**
   * The path alias manager.
   */
  private function getAliasManager(): AliasManagerInterface {
    if (!$this->aliasManager) {
      $this->aliasManager = \Drupal::service('path_alias.manager');
    }
    return $this->aliasManager;
  }

  /**
   * The transliteration service.
   */
  private function getTransliteration(): TransliterationInterface {
    if (!$this->transliteration) {
      $this->transliteration = \Drupal::service('transliteration');
    }
    return $this->transliteration;
  }

  /**
   * Matches the path as a Drupal route, then as a system route.
   *
   * When nothing matches, the error page route is used and $output and $info
   * describe the 404.
   */
  private function matchPath(Request $request, string $path, array &$output, array $error_match_info, &$info): array {
    try {
      // Drupal routes.
      return $this->router->match($path);
    }
    catch (\Exception $exception) {
      try {
        // System routes.
        $info = $this->getRouteFromRequest($request);
        return $this->router->match($info['path']);
      }
      catch (\Exception $e) {
        $info = [
          '_route' => 'error_page',
        ];
        $output['status'] = 404;
        $output['message'] = "No route found for $path";
        return $error_match_info;
      }
    }
  }

  /**
   * Applies the view access of a content entity to the output.
   *
   * An anonymous user is redirected to the login page; another user gets the
   * error page with a 403.
   *
   * @return bool
   *   FALSE when the account_login system route is missing.
   */
  private function applyViewAccess(EntityInterface &$entity, string $path, array &$output, array $error_match_info, &$info, array &$redirects_trace): bool {
    if (!$entity->getEntityType() instanceof ContentEntityType || $entity->access('view', NULL, TRUE)->isAllowed()) {
      return TRUE;
    }
    if (!$this->currentUser()->isAnonymous()) {
      $entity = $this->findEntity($error_match_info);
      $info = [
        '_route' => 'error_page',
      ];
      $output['status'] = 403;
      $output['message'] = "User is not allowed to view $path";
      return TRUE;
    }
    // Redirect the anonymous user to the login page in case.
    // they do not have access to the current page.
    $login_route = $this->systemRoutes['account_login'];
    if (!isset($login_route)) {
      $this->logger->error('System route account_login is not found. Create one at /admin/config/system/vactory_router');
    }
    else {
      $redirects_trace[] = [
        'to' => '/' . $this->currentLangcode . $login_route->getAlias(),
        'status' => 301,
        'from' => $path,
      ];
      $output['redirect'] = $redirects_trace;
    }
    return isset($login_route);
  }

  /**
   * Check if path follow the /node/nid pattern.
   */
  private function isFollowNodePathPattern($path) {
    // Define the pattern.
    $pattern = '/\/node\/\d+$/';

    // Check if the path matches the pattern.
    return preg_match($pattern, $path);
  }

  /**
   * Extract path from request.
   */
  private function getPathFromRequest(Request $request) {
    $path = $request->query->get('path');
    if (empty($path)) {
      throw new NotFoundHttpException('Unable to translate empty path. Please send a ?path query string parameter with your request.');
    }

    return $this->cleanSubdirInPath($path, $request);
  }

  /**
   * Return information about the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param int $status
   *   The resolved response status. A 403/404 status is passed to the JSON:API
   *   individual URL so the blocks "Response status" visibility condition can
   *   be evaluated against it.
   */
  protected function getEntityOutput(EntityInterface $entity, $status = 200) {
    $entity_type_id = $entity->getEntityTypeId();
    $rt = $this->jsonapiResourceTypeRepository->get($entity_type_id, $entity->bundle());
    $type_name = $rt->getTypeName();
    $route_name = sprintf('jsonapi.%s.individual', $type_name);
    $options = ['absolute' => TRUE];
    if (in_array($status, [403, 404], TRUE)) {
      $options['query'][BlocksManager::RESPONSE_STATUS_QUERY_PARAMETER] = $status;
    }
    $individual = Url::fromRoute(
      $route_name,
      [
        static::getEntityRouteParameterName($route_name, $entity_type_id) => $entity->uuid(),
      ],
      $options
    )->toString(TRUE);

    $output = [
      'entity' => [
        'type' => $entity_type_id,
        'bundle' => $entity->bundle(),
        'label' => $entity->label(),
        'uuid' => $entity->uuid(),
        'id' => $entity->id() ?? NULL,
      ],
    ];

    $output['jsonapi'] = [
      'individual' => $individual->getGeneratedUrl(),
      'resourceName' => $type_name,
    ];

    return $output;
  }

  /**
   * Removes the subdir prefix from the path.
   *
   * @param string $path
   *   The path that can contain the subdir prefix.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to extract the path prefix from.
   *
   * @return string
   *   The clean path.
   */
  protected function cleanSubdirInPath($path, Request $request) {
    // Remove any possible leading subdir information in case Drupal is
    // installed under http://example.com/d8/index.php
    $regexp = preg_quote($request->getBasePath(), '/');
    return preg_replace(sprintf('/^%s/', $regexp), '', $path);
  }

  /**
   * Match path against a collection of routes.
   *
   * @return array
   *   Route configuration or empty array
   */
  private function getRouteFromRequest(Request $request) {
    $path = $this->getPathFromRequest($request);

    $routes = new RouteCollection();

    foreach ($this->systemRoutes as $route) {
      $auxRoute = new Route($route->getAlias());
      $auxRoute->setOption("utf8", TRUE);
      $routes->add($route->id(), $auxRoute);
    }

    $context = new RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($routes, $context);

    $match_info = [];
    $data = $matcher->match($path);
    $match_info = [
      '_query' => $data,
      '_route' => $data['_route'],
    ];
    unset($match_info['_query']['_route']);
    $route = $this->systemRoutes[$match_info['_route']];
    $match_info['path'] = $route->getPath();
    $match_info['alias'] = $route->getAlias();
    $match_info['request_path'] = $path;

    return $match_info;
  }

  /**
   * Get the underlying entity and the type of ID param enhancer for the routes.
   *
   * @param array $match_info
   *   The router match info.
   * @param bool $preview
   *   Is preview mode.
   *
   * @return array
   *   The pair of \Drupal\Core\Entity\EntityInterface and bool with the
   *   underlying entity and the info weather or not it uses UUID for the param
   *   enhancement. It also returns the name of the parameter under which the
   *   entity lives in the route ('node' vs 'entity').
   */
  protected function findEntity(array $match_info, $preview = FALSE) {
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $match_info[RouteObjectInterface::ROUTE_OBJECT];
    $entity = $this->getRouteEntity($match_info);

    // Do not return unpublished entity.
    if ($entity) {
      $entity_type_id = $this->findEntityTypeFromRoute($route);
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $status = $entity_type_definition->getKey('status');
      $status = !$status ? $entity_type_definition->getKey('published') : $status;
      $entity_status = $entity->get($status)->value;
      if (!$entity_status) {
        // Entity is unpublished.
        $account = \Drupal::currentUser();
        // If not preview OR user cannot edit → return NULL.
        if (!$preview || !$entity->access('update', $account)) {
          $entity = NULL;
        }
      }
    }

    return $entity;
  }

  /**
   * The entity of a matched route, whatever its status.
   *
   * @param array $match_info
   *   The router match info.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity, or NULL when the route has none.
   */
  private function getRouteEntity(array $match_info): ?EntityInterface {
    $entity = $match_info['entity'] ?? NULL;
    if (!$entity instanceof EntityInterface) {
      // @todo $match_info[$entity_type_id] is broken for JSON API 2.x routes.
      // Now it will be $match_info[$entity_type_id] for core and
      // $match_info['entity'] for JSON API :-(.
      $entity_type_id = $this->findEntityTypeFromRoute($match_info[RouteObjectInterface::ROUTE_OBJECT]);
      $entity = empty($entity_type_id) ? NULL : $match_info[$entity_type_id] ?? NULL;
    }
    return $entity instanceof EntityInterface ? $entity : NULL;
  }

  /**
   * Computes the name of the entity route parameter for JSON API routes.
   *
   * @param string $route_name
   *   A JSON API route name.
   * @param string $entity_type_id
   *   The corresponding entity type ID.
   *
   * @return string
   *   Either 'entity' or $entity_type_id.
   *
   * @todo Remove this once decoupled_router requires jsonapi >= 8.x-2.0.
   */
  protected static function getEntityRouteParameterName($route_name, $entity_type_id) {
    static $first;

    if (!isset($first)) {
      $route_parameters = \Drupal::service('router.route_provider')
        ->getRouteByName($route_name)
        ->getOption('parameters');
      $first = isset($route_parameters['entity'])
        ? 'entity'
        : $entity_type_id;
      return $first;
    }

    return $first === 'entity'
      ? 'entity'
      : $entity_type_id;
  }

  /**
   * Extracts the entity type for the route parameters.
   *
   * If there are more than one parameter, this function will return the first
   * one.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   *
   * @return string|null
   *   The entity type ID or NULL if not found.
   */
  protected function findEntityTypeFromRoute(Route $route) {
    $parameters = (array) $route->getOption('parameters');
    // Find the entity type for the first parameter that has one.
    return array_reduce($parameters, function ($carry, $parameter) {
      if (!$carry && !empty($parameter['type'])) {
        $parts = explode(':', $parameter['type']);
        // We know that the parameter is for an entity if the type is set to
        // 'entity:<entity-type-id>'.
        if ($parts[0] === 'entity' && !empty($parts[1])) {
          $carry = $parts[1];
        }
      }
      return $carry;
    }, NULL);
  }

}
