<?php

namespace Drupal\vactory_decoupled;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Simplifies the process of generating a JSON:API.
 *
 * @api
 */
class JsonApiClient {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The JSON:API Resource Type Repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * A Session object.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $currentRequest;

  /**
   * EntityToJsonApi constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The stack of requests.
   */
  public function __construct(
    HttpKernelInterface $http_kernel,
    ResourceTypeRepositoryInterface $resource_type_repository,
    SessionInterface $session,
    RequestStack $request_stack
  ) {
    $this->httpKernel = $http_kernel;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->session = $this->currentRequest->hasPreviousSession()
      ? $this->currentRequest->getSession()
      : $session;
  }

  /**
   * Return the requested entity as a raw string.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   * @param string[] $includes
   *   The list of includes.
   *
   * @return string
   *   The raw JSON string of the requested resource.
   *
   * @throws \Exception
   */
  public function serialize($resource_name, $query = []) {
    // @todo: how can we pass parameters from next to this.
    // @todo: clean json response, specialy "links" pay attention to pagination
    $resource_type = $this->resourceTypeRepository->getByTypeName($resource_name);
    $route_type = 'collection';
    $route_name = sprintf('jsonapi.%s.%s', $resource_type->getTypeName(), $route_type);

    $jsonapi_url = Url::fromRoute($route_name)
      ->toString(TRUE)
      ->getGeneratedUrl();

    // Get current page informations and pass them through the next request.
    $params = \Drupal::routeMatch()->getParameters();
    if ($params) {
      $params_query = \Drupal::request()->query->get("q") ?? [];
      if ($resource_type_param = $params->get('resource_type')) {
        $params_query["entity_bundle"] = $resource_type_param instanceof ResourceType ?  $resource_type_param->getBundle() : $resource_type_param;
      }

      if ($entity_param = $params->get('entity')) {
        // Sending uuid query string be4419f2-c6f0-4bfa-a8a2-5b21081126d9
        // breaks and violate the JSON:API spec.
        // $params_query["entity_uuid"] = $entity_param->uuid();
        $params_query["entity_id"] = $entity_param->id();
      }

      $query['q'] = $params_query;
    }

    $request = Request::create(
      $jsonapi_url,
      'GET',
      $query,
      $this->currentRequest->cookies->all(),
      [],
      $this->currentRequest->server->all()
    );
    if ($this->session) {
      $request->setSession($this->session);
    }

    // This is used to retrieve Cacheability Metadata from JSON:API
    $request->headers->set("X-Internal-Cacheability-Debug", "true");
    \Drupal::logger('vactory_decoupled')->info('Request created: @url', ['@url' => urldecode($request->getUri())]);

    $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);

    return [
      "data" => $response->getContent(),
      "cache" => [
        "tags" => explode(" ", $response->headers->get('x-drupal-cache-tags'))
      ]
    ];
  }

  // @todo: need a caller type here.
  // this should be added a q[request_type]=json_api_collection|blocks ...
  public function serializeIndividual($entity, $query = []) {
    // @todo: clean json response, specialy "links" pay attention to pagination
    $resource_type = $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->bundle());
    $route_name = sprintf('jsonapi.%s.individual', $resource_type->getTypeName());
    $route_options = [];
    $jsonapi_url = Url::fromRoute($route_name, ['entity' => $entity->uuid()], $route_options)
      ->toString(TRUE)
      ->getGeneratedUrl();

    // Get current page informations and pass them through the next request.
    $params = \Drupal::routeMatch()->getParameters();
    if ($params) {
      $params_query = \Drupal::request()->query->get("q") ?? [];
      if ($resource_type_param = $params->get('resource_type')) {
        $params_query["entity_bundle"] = $resource_type_param instanceof ResourceType ?  $resource_type_param->getBundle() : $resource_type_param;
      }

      if ($entity_param = $params->get('entity')) {
        // Sending uuid query string be4419f2-c6f0-4bfa-a8a2-5b21081126d9
        // breaks and violate the JSON:API spec.
        // $params_query["entity_uuid"] = $entity_param->uuid();
        $params_query["entity_id"] = $entity_param->id();
      }

      $query['q'] = $params_query;
    }

    $request = Request::create(
      $jsonapi_url,
      'GET',
      $query,
      $this->currentRequest->cookies->all(),
      [],
      $this->currentRequest->server->all()
    );
    if ($this->session) {
      $request->setSession($this->session);
    }

    // This is used to retrieve Cacheability Metadata from JSON:API
    $request->headers->set("X-Internal-Cacheability-Debug", "true");
    \Drupal::logger('vactory_decoupled')->info('Request created: @url', ['@url' => urldecode($request->getUri())]);

    $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);

    return [
      "data" => $response->getContent(),
      "cache" => [
        "tags" => explode(" ", $response->headers->get('x-drupal-cache-tags'))
      ]
    ];
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   * @param string[] $includes
   *   The list of includes.
   *
   * @return array
   *   The JSON structure of the requested resource.
   *
   * @throws \Exception
   */
  public function normalize(EntityInterface $entity, array $includes = []) {
    return Json::decode($this->serialize($entity, $includes));
  }

}
