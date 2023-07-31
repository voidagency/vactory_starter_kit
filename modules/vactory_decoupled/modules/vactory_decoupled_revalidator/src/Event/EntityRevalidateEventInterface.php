<?php

namespace Drupal\vactory_decoupled_revalidator\Event;

use Drupal\Core\Entity\EntityInterface;

interface EntityRevalidateEventInterface {

  /**
   * The entity insert/update/delete action.
   */
  public const ENTITY_REVALIDATED_ACTION = 'vactory.entity.revalidated';

  /**
   * The entity insert action.
   */
  public const INSERT_ACTION = 'insert';

  /**
   * The entity update action.
   */
  public const UPDATE_ACTION = 'update';

  /**
   * The entity delete action. We use predelete because we need access to the entity for revalidating.
   */
  public const DELETE_ACTION = 'delete';

  /**
   * Get the entity for the action.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity
   */
  public function getEntity(): EntityInterface;

  /**
   * Sets the entity for the action.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *   The event entity.
   */
  public function setEntity(EntityInterface $entity): self;

  /**
   * @return string
   */
  public function getAction(): string;

  /**
   * @param string $action
   *
   * @return \Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEventInterface
   */
  public function setAction(string $action): self;

  /**
   * @return null|string
   */
  public function getEntityUrl(): ?string;

  /**
   * @param string $url
   *
   * @return \Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEventInterface
   */
  public function setEntityUrl(string $url): self;

}