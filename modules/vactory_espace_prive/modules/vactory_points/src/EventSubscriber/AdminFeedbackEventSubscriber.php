<?php

namespace Drupal\vactory_points\EventSubscriber;

use Drupal\admin_feedback\Event\VoteEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\vactory_points\Services\VactoryPointsManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Admin feedback event subscriber.
 */
class AdminFeedbackEventSubscriber implements EventSubscriberInterface {

  /**
   * Vactory points manager service.
   *
   * @var \Drupal\vactory_points\Services\VactoryPointsManagerInterface
   */
  protected $vactoryPointsManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Feedback event subscriber constructor.
   *
   * @param \Drupal\vactory_points\Services\VactoryPointsManagerInterface $vactoryPointsManager
   *   Vactory points manager service.
   * @param \Drupal\vactory_points\Services\VactoryPointsManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(VactoryPointsManagerInterface $vactoryPointsManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->vactoryPointsManager = $vactoryPointsManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[VoteEvent::VOTE_EVENT] = 'onFeedback';
    return $events;
  }

  /**
   * On feedback event handler.
   */
  public function onFeedback(VoteEvent $event) {
    $action = 'feedback';
    if (is_numeric($event->getNid())) {
      $entity = $this->entityTypeManager->getStorage('node')
        ->load($event->getNid());
    }
    if (!$entity instanceof NodeInterface) {
      $entity = NULL;
    }
    $this->vactoryPointsManager->triggerUserPointsUpdate($action, $entity);
  }

}
