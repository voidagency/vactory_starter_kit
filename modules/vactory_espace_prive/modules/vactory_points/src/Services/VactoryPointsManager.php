<?php

namespace Drupal\vactory_points\Services;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;
use Drupal\vactory_points\Event\VactoryPointsEditEvent;

/**
 * Vactory Points Manager service.
 */
class VactoryPointsManager implements VactoryPointsManagerInterface {

  /**
   * Event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Vactory points manager service constructor.
   *
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher
   *   Event dispatcher service.
   */
  public function __construct(ContainerAwareEventDispatcher $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritDoc}
   */
  public function triggerUserPointsUpdate(string $action, EntityInterface $entity = NULL, UserInterface $user = NULL) {
    $event = new VactoryPointsEditEvent($action, $entity, $user);
    $this->eventDispatcher->dispatch(VactoryPointsEditEvent::EVENT_NAME, $event);
  }

}
