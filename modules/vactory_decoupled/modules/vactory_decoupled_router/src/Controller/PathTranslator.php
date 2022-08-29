<?php

namespace Drupal\vactory_decoupled_router\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\Routing;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;

/**
 * Controller that receives the path to inspect.
 */
class PathTranslator extends ControllerBase
{

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
  private $jsonapi_resource_type_respository;

  /**
   * System routes.
   */
  protected $systemRoutes = [];

  /**
   * EventInfoController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   */
  public function __construct(
    LoggerInterface $logger,
    UrlMatcherInterface $router,
    ResourceTypeRepository $jsonapi_resource_type_respository
  ) {
    $this->logger = $logger;
    $this->router = $router;
    $this->jsonapi_resource_type_respository = $jsonapi_resource_type_respository;

    $this->systemRoutes = \Drupal::entityTypeManager()->getStorage('vactory_route')->loadMultiple();
  }

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('logger.channel.vactory_decoupled_router'),
      $container->get('router.no_access_checks'),
      $container->get('jsonapi.resource_type.repository')
    );
  }

  /**
   * Responds with all the information about the path.
   */
  public function translate(Request $request)
  {
    $path = $this->getPathFromRequest($request);

    if (!isset($this->systemRoutes['error_page'])) {
      $this->logger->error('System route error_page is not found. Create one at /admin/config/system/vactory_router');
      return;
    }

    /** @var \Drupal\vactory_decoupled_router\Entity\Route $error_route */
    $error_route = $this->systemRoutes['error_page'];
    $error_match_info = $this->router->match($error_route->getPath());

    // Assume a 200 from start.
    $this->response = JsonResponse::create([],200);

    $output = [];
    $output['status'] = 200;
    $info = NULL;
    try {
      // System routes.
      $info = $this->getRouteFromRequest($request);
      $match_info = $this->router->match($info['path']);
    } catch (\Exception $exception) {
      try {
        // Drupal routes.
        $match_info = $this->router->match($path);
      } catch (\Exception $e) {
        // $this->response->setStatusCode(404);
        $info = [
          '_route' => 'error_page',
        ];
        $output['status'] = 404;
        $output['message'] = "Not route found for $path";
        $match_info = $error_match_info;
      }
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->findEntity($match_info);
    if (!$entity) {
      $this->logger->notice('A route has been found but it has no entity information.');
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->findEntity($error_match_info);
      $info = [
        '_route' => 'error_page',
      ];
      $output['status'] = 500;
      // $this->response->setStatusCode(500);
    }

    if ($entity->getEntityType() instanceof ContentEntityType) {
      $can_view = $entity->access('view', NULL, TRUE);
      if (!$can_view->isAllowed()) {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $this->findEntity($error_match_info);
        $info = [
          '_route' => 'error_page',
        ];
        $output['status'] = 403;
        // $this->response->setStatusCode(403);
        $output['message'] = "User is not allowed to view $path";
      }
    }

    $output = array_merge($this->getEntityOutput($entity), $output);

    if ($info) {
      $output['system'] = $info;
    }

    $this->response->headers->add(['Content-Type' => 'application/json']);
    $this->response->setData($output);

    return $this->response;
  }

  /**
   * Extract path from request.
   */
  private function getPathFromRequest(Request $request)
  {
    $path = $request->query->get('path');
    if (empty($path)) {
      throw new NotFoundHttpException('Unable to translate empty path. Please send a ?path query string parameter with your request.');
    }

    return $this->cleanSubdirInPath($path, $request);
  }

  /**
   * Return information about the entity.
   */
  protected function getEntityOutput($entity)
  {
    $entity_type_id = $entity->getEntityTypeId();
    $rt = $this->jsonapi_resource_type_respository->get($entity_type_id, $entity->bundle());
    $type_name = $rt->getTypeName();
    $route_name = sprintf('jsonapi.%s.individual', $type_name);
    $individual = Url::fromRoute(
      $route_name,
      [
        static::getEntityRouteParameterName($route_name, $entity_type_id) => $entity->uuid(),
      ],
      ['absolute' => TRUE]
    )->toString(TRUE);

    $output = [
      'entity' => [
        'type' => $entity_type_id,
        'bundle' => $entity->bundle(),
        'label' => $entity->label(),
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
  protected function cleanSubdirInPath($path, Request $request)
  {
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
  private function getRouteFromRequest(Request $request)
  {
    $path = $this->getPathFromRequest($request);
   
    // @todo: cache this ?
    $routes = new Routing\RouteCollection();

    foreach ($this->systemRoutes as $route) {
      $routes->add($route->id(), new Routing\Route($route->getAlias()));
    }

    $context = new RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($routes, $context);

    $match_info = [];
    $data = $matcher->match($path);
    $match_info = [
      '_query' => $data,
      '_route' => $data['_route']
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
   *
   * @return array
   *   The pair of \Drupal\Core\Entity\EntityInterface and bool with the
   *   underlying entity and the info weather or not it uses UUID for the param
   *   enhancement. It also returns the name of the parameter under which the
   *   entity lives in the route ('node' vs 'entity').
   */
  protected function findEntity(array $match_info)
  {
    $entity = NULL;
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $match_info[RouteObjectInterface::ROUTE_OBJECT];
    if (
      !empty($match_info['entity']) &&
      $match_info['entity'] instanceof EntityInterface
    ) {
      $entity = $match_info['entity'];
    } else {
      $entity_type_id = $this->findEntityTypeFromRoute($route);
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      // @todo $match_info[$entity_type_id] is broken for JSON API 2.x routes.
      // Now it will be $match_info[$entity_type_id] for core and
      // $match_info['entity'] for JSON API :-(.
      if (
        !empty($entity_type_id) &&
        !empty($match_info[$entity_type_id]) &&
        $match_info[$entity_type_id] instanceof EntityInterface
      ) {
        $entity = $match_info[$entity_type_id];
      }
    }

    return $entity;
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
  protected static function getEntityRouteParameterName($route_name, $entity_type_id)
  {
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
  protected function findEntityTypeFromRoute(Route $route)
  {
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
