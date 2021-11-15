<?php

namespace Drupal\vactory_sondage\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Vactory Point Edit Event class.
 */
class VactorySondageVoteEvent extends Event {

  /**
   * Event name.
   */
  const EVENT_NAME = 'vactory_sondage_vote_event';

  /**
   * The concerned entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The votter entity.
   *
   * @var \Drupal\user\UserInterface
   */
  private $votter;

  /**
   * Vactory Points Increment Event constructor.
   */
  public function __construct(EntityInterface $entity = NULL, UserInterface $votter) {
    $this->entity = $entity;
    $this->votter = $votter;
  }

  /**
   * Votter setter.
   *
   * @param \Drupal\user\UserInterface $user
   *   The votter entity.
   */
  public function setVotter(UserInterface $user): void {
    $this->votter = $user;
  }

  /**
   * Votter getter.
   *
   * @return \Drupal\user\UserInterface
   *   The votter entity.
   */
  public function getVotter() {
    return $this->votter;
  }

  /**
   * Concerned entity getter.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The concerned entity object.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Concerned entity setter.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The concerned entity object.
   */
  public function setEntity(EntityInterface $entity): void {
    $this->entity = $entity;
  }

}
