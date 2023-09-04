<?php

namespace Drupal\vactory_decoupled\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class RequestSubscriberCheckLimits.
 */
class RequestSubscriberLimitiAuthAccess implements EventSubscriberInterface {

  /**
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new RequestSubscriberCheckLimits object.
   */
  public function __construct(RouterInterface $router, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->router = $router;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onRequest'];

    return $events;
  }

  /**
   * Check the limits on the request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   When the system is flooded.
   * @throws \Exception
   *   When the flood table does not exist.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    try {
      $route_data = $this->router->matchRequest($request);
    } catch (\Exception $e) {
      return;
    }
    /** @var \Symfony\Component\Routing\Route $route */
    $route_name = $route_data[RouteObjectInterface::ROUTE_NAME];
    if ($route_name === 'vactory_decoupled.login_token') {
      $username = $request->request->get('username');
      if (!empty($username)) {

        $account_search = $this->entityTypeManager
          ->getStorage('user')
          ->loadByProperties(['name' => $username]);

        if ($account = reset($account_search)) {

          $roles_to_be_excluded = $this->configFactory->get('vactory_decoupled.settings')
              ->get('auth_roles_excluded') ?? [];

          if (count(array_intersect($account->getRoles(), $roles_to_be_excluded)) > 0) {
            $response = new JsonResponse([
              'error' => 'auth_access_limit',
              'message' => 'Access denied',
            ], 403);
            $event->setResponse($response);
            return FALSE;
          }

        }

      }

    }
  }

}
