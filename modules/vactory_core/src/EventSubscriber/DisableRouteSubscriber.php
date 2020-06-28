<?php

namespace Drupal\vactory_core\EventSubscriber;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

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
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event triggered by the request.
   */
  public function onRequest(Event $event) {
    return $this->processEvent($event);
  }

  /**
   * A method to be called whenever a kernel.response event is dispatched.
   *
   * Like the onRequest event, it  passes in a response.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event triggered by the response.
   */
  public function onResponse(Event $event) {
    return $this->processEvent($event);
  }

  /**
   * Process events generically invoking rabbit hole behaviors if necessary.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event to process.
   */
  private function processEvent(Event $event) {
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
