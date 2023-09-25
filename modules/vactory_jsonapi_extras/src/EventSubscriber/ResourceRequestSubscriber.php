<?php

namespace Drupal\vactory_jsonapi_extras\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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
      }
    }
  }

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
        $request->query->set($name, $value);
      }
    }
  }

}
