<?php

namespace Drupal\vactory_points\EventSubscriber;

use Drupal\vactory_points\Services\VactoryPointsManagerInterface;
use Drupal\vactory_sondage\Event\VactorySondageVoteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sondage vote event subscriber class.
 */
class SondageVoteEventsSubscriber implements EventSubscriberInterface {

  /**
   * Vactory points manager service.
   *
   * @var \Drupal\vactory_points\Services\VactoryPointsManagerInterface
   */
  protected $vactoryPointsManager;

  /**
   * Sondage vote event subscriber constructor.
   *
   * @param \Drupal\vactory_points\Services\VactoryPointsManagerInterface $vactoryPointsManager
   *   Vactory points manager service.
   */
  public function __construct(VactoryPointsManagerInterface $vactoryPointsManager) {
    $this->vactoryPointsManager = $vactoryPointsManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      VactorySondageVoteEvent::EVENT_NAME => 'onVote',
    ];
  }

  /**
   * On vote event handler.
   */
  public function onVote(VactorySondageVoteEvent $event) {
    $action = 'vote';
    $this->vactoryPointsManager->triggerUserPointsUpdate($action, $event->getEntity());
  }

}
