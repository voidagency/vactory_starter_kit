<?php

namespace Drupal\vactory_points\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Vactory points manager service interface.
 */
interface VactoryPointsManagerInterface {

  /**
   * Trigger user points update event.
   *
   * @param string $action
   *   The action key.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Concerned entity.
   * @param \Drupal\user\UserInterface|null $user
   *   Concerned user entity.
   */
  public function triggerUserPointsUpdate(string $action, EntityInterface $entity = NULL, UserInterface $user = NULL);

}
