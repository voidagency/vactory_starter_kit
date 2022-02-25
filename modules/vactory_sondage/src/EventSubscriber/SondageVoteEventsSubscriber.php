<?php

namespace Drupal\vactory_sondage\EventSubscriber;

use Drupal\vactory_sondage\Event\VactorySondageVoteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sondage vote event subscriber class.
 */
class SondageVoteEventsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[VactorySondageVoteEvent::EVENT_NAME] = 'onVote';
    return $events;
  }

  /**
   * On vote event handler.
   */
  public function onVote(VactorySondageVoteEvent $event) {
    if (\Drupal::moduleHandler()->moduleExists('vactory_points')) {
      $action = 'vote';
      \Drupal::service('vactory_points.manager')->triggerUserPointsUpdate($action, $event->getEntity());
    }
  }

}
