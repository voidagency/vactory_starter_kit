<?php

namespace Drupal\vactory_core\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the dynamic route events.
 */
class DisableRouteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events['kernel.request'] = ['onRequest', 28];
    $events['kernel.response'] = ['onResponse'];
    return $events;
  }

  /**
   * A method to be called whenever a kernel.request event is dispatched.
   *
   * @param RequestEvent $event
   *   The event triggered by the request.
   */
  public function onRequest(RequestEvent $event) {
    return $this->processEvent($event);
  }

  /**
   * A method to be called whenever a kernel.response event is dispatched.
   *
   * Like the onRequest event, it  passes in a response.
   *
   * @param ResponseEvent $event
   *   The event triggered by the response.
   */
  public function onResponse(ResponseEvent $event) {
    return $this->processEvent($event);
  }

  /**
   * Process events generically invoking rabbit hole behaviors if necessary.
   *
   * @param ResponseEvent|RequestEvent $event
   *   The event to process.
   */
  private function processEvent(ResponseEvent|RequestEvent $event) {
    // Don't process events with HTTP exceptions - those have either been thrown
    // by us or have nothing to do with rabbit hole.
    if ($event->getRequest()->get('exception') != NULL) {
      return;
    }
    // Get the route from the request.
    if ($route = $event->getRequest()->get('_route')) {
      // Only continue if the request route is the an entity canonical.
      if (preg_match('/^entity\.taxonomy_term\.canonical$/', $route)) {
        throw new NotFoundHttpException();
      }
    }
  }

}
