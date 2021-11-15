<?php

namespace Drupal\vactory_points\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Vactory Point Edit Event class.
 */
class VactoryPointsEditEvent extends Event {

  /**
   * Event name.
   */
  const EVENT_NAME = 'vactory_points_edit_event';

  /**
   * The event action.
   *
   * @var string
   */
  private $action;

  /**
   * The concerned entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The concerned user.
   *
   * @var \Drupal\user\UserInterface
   */
  private $user;

  /**
   * Vactory Points Increment Event constructor.
   */
  public function __construct(string $action, EntityInterface $entity = NULL, UserInterface $user = NULL) {
    $this->entity = $entity;
    $this->action = $action;
    $this->user = $user;
  }

  /**
   * Set concerned user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The concerned user entity.
   */
  public function setConcernedUser(UserInterface $user): void {
    $this->user = $user;
  }

  /**
   * Get concerned user.
   *
   * @return \Drupal\user\UserInterface
   *   The concerned user entity.
   */
  public function getConcernedUser() {
    return $this->user;
  }

  /**
   * Action setter.
   *
   * @param string $action
   *   The triggering action.
   */
  public function setAction($action): void {
    $this->action = $action;
  }

  /**
   * Action getter.
   *
   * @return string
   *   The triggering action.
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * The concerned entity getter.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The concerned entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * The concerned entity setter.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The concerned entity.
   */
  public function setEntity(EntityInterface $entity): void {
    $this->entity = $entity;
  }

}
