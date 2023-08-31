<?php

namespace Drupal\vactory_core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Remove X-Generator header response.
 */
class RemoveXGeneratorResponseHeader implements EventSubscriberInterface {

  /**
   * A method to be called whenever a kernel.response event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event triggered by the request.
   */
  public function removeXgenerator(ResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->remove('X-Generator');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['removeXgenerator', -10];
    return $events;
  }

}
