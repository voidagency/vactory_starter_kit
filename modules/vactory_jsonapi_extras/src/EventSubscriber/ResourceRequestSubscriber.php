<?php

namespace Drupal\vactory_jsonapi_extras\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Request subscriber.
 */
class ResourceRequestSubscriber implements EventSubscriberInterface {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a ResourceRequestSubscriber object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $routeMatch, Token $token) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber::getSubscribedEvents()
   * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber
   */
  public static function getSubscribedEvents() {
    // Run before the dynamic page cache subscriber (priority 100), so that
    // Dynamic Page Cache can cache flattened responses.
    $events[KernelEvents::REQUEST][] = ['onRequest', 1];
    $events[KernelEvents::RESPONSE][] = ['onResponse', 1];
    return $events;
  }

  /**
   * Serializes ResourceResponse responses' data, and removes that data.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $route_name = $this->routeMatch->getRouteName();
    if (strpos($route_name, 'exposed_api.') !== 0) {
      return;
    }
    $id = $this->routeMatch->getParameter('exposed_api');
    if ($id) {
      $partner_api = $this->entityTypeManager->getStorage('exposed_apis')
        ->load($id);
      if ($partner_api) {
        $includes = $partner_api->getIncludes();
        $fields = $partner_api->getFields();
        $filters = $partner_api->getFilters();
        $this->parseQueryParams($includes, $request);
        $this->parseQueryParams($fields, $request);
        $this->parseQueryParams($filters, $request);
        if ($partner_api->isJsonApiInclude()) {
          $request->query->set('jsonapi_include', 1);
        }
        $request->overrideGlobals();
      }
    }
  }

  /**
   * Sets extra headers on successful responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event) {
    $route_name = $this->routeMatch->getRouteName();
    if (empty($route_name) || !str_starts_with($route_name, 'exposed_api.')) {
      return;
    }
    $id = $this->routeMatch->getParameter('exposed_api');
    if ($id) {
      $response = $event->getResponse();
      $content = Json::decode($response->getContent());

      $partner_api = $this->entityTypeManager->getStorage('exposed_apis')
        ->load($id);

      if (!$partner_api) {
        return;
      }

      // Get default filters.
      $fields = str_replace(["\r\n", "\n"], '&', $partner_api->getFields());
      $filters = str_replace(["\r\n", "\n"], '&', $partner_api->getFilters());

      if (isset($content['links']['last']['href'])) {
        $content['links']['last']['href'] = $this->clearJsonApiFilters($id, $content['links']['last']['href'], $fields, $filters);
      }
      if (isset($content['links']['next']['href'])) {
        $content['links']['next']['href'] = $this->clearJsonApiFilters($id, $content['links']['next']['href'], $fields, $filters);
      }
      if (isset($content['links']['self']['href'])) {
        $content['links']['self']['href'] = $this->clearJsonApiFilters($id, $content['links']['self']['href'], $fields, $filters);
      }
      if (isset($content['links']['first']['href'])) {
        $content['links']['first']['href'] = $this->clearJsonApiFilters($id, $content['links']['first']['href'], $fields, $filters);
      }
      if (isset($content['links']['prev']['href'])) {
        $content['links']['prev']['href'] = $this->clearJsonApiFilters($id, $content['links']['prev']['href'], $fields, $filters);
      }
      if (isset($content['meta']['facets'])) {
        foreach ($content['meta']['facets'] as $i => $facet) {
          if (!isset($facet['terms']) || empty($facet['terms'])) {
            continue;
          }
          foreach ($facet['terms'] as $j => $term) {
            if (!isset($term['url']) || empty($term['url'])) {
              continue;
            }
            $content['meta']['facets'][$i]['terms'][$j]['url'] = $this->clearJsonApiFilters($id, $content['meta']['facets'][$i]['terms'][$j]['url'], $fields, $filters);
          }
        }
      }
    }

    $response->setContent(Json::encode($content));
  }

  /**
   * Clear jsonapi filters from url.
   */
  public function clearJsonApiFilters($id, string $url, $fields, $filters) {
    $url_infos = parse_url($url);
    if (!isset($url_infos['query'])) {
      return $url;
    }

    $query_params = [];
    parse_str($url_infos['query'], $query_params);

    if (!empty($fields)) {
      $default_fields = [];
      parse_str($fields, $default_fields);
      if (!empty($default_fields)) {
        // Remove default fields from link.
        foreach ($query_params['fields'] as $key => $value) {
          if (isset($default_fields['fields'][$key])) {
            unset($query_params['fields'][$key]);
          }
        }
        if (empty($query_params['fields'])) {
          unset($query_params['fields']);
        }
        // Remove default page params from link.
        foreach ($query_params['page'] as $key => $value) {
          if (isset($default_fields['page'][$key])) {
            unset($query_params['page'][$key]);
          }
        }
        if (empty($query_params['page'])) {
          unset($query_params['page']);
        }
      }
    }
    if (!empty($filters)) {
      $default_filters = [];
      parse_str($filters, $default_filters);
      if (!empty($default_filters)) {
        // Remove default filters from link.
        foreach ($query_params['filter'] as $key => $value) {
          if (isset($default_filters['filter'][$key])) {
            unset($query_params['filter'][$key]);
          }
        }
        if (empty($query_params['filter'])) {
          unset($query_params['filter']);
        }
        // Remove default page params from link.
        foreach ($query_params['page'] as $key => $value) {
          if (isset($default_filters['page'][$key])) {
            unset($query_params['page'][$key]);
          }
        }
        if (empty($query_params['page'])) {
          unset($query_params['page']);
        }
      }
    }

    // Remove includes param from Links.
    unset($query_params['include']);
    unset($query_params['jsonapi_include']);

    if (!empty($query_params)) {
      return str_replace($url_infos['query'], http_build_query($query_params), $url);
    }

    return str_replace('?' . $url_infos['query'], '', $url);
  }

  /**
   * Parse query params.
   */
  protected function parseQueryParams($params_string, Request $request) {
    $params_string = $this->token->replace($params_string, []);
    $params_string = trim($params_string);
    $params_string = str_replace("\n", '&', $params_string);
    $params_string = str_replace("\r", '', $params_string);
    $filters = [];
    parse_str($params_string, $filters);
    foreach ($filters as $name => $value) {
      $name = trim($name);
      if (!empty($name) && !empty($value)) {
        if ($name === 'filter') {
          $recieved_filters = $request->query->get('filter', []);
          $value = array_merge($recieved_filters, $value);
        }
        if ($name === 'page') {
          $recieved_filters = $request->query->get('page', []);
          $value = array_merge($recieved_filters, $value);
        }
        $request->query->set($name, $value);
      }
    }
  }

}
