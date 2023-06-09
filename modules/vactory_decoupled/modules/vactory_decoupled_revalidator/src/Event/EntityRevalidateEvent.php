<?php

namespace Drupal\vactory_decoupled_revalidator\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an entity action event.
 *
 */
class EntityRevalidateEvent extends Event implements EntityRevalidateEventInterface {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The action.
   *
   * @var string
   */
  protected $action;

  /**
   * The entity Url.
   *
   * @var string|null
   */
  protected $entityUrl;

  /**
   * EntityRevalidateEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $action
   * @param null|string $entity_url
   */
  public function __construct(EntityInterface $entity, string $action, ?string $entity_url) {
    $this->entity = $entity;
    $this->action = $action;
    $this->entityUrl = $entity_url;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $action
   *
   * @return \Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEvent
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function createFromEntity(EntityInterface $entity, string $action): self {
    $url = $entity->id() && $entity->hasLinkTemplate('canonical') ? $entity->toUrl()
      ->toString(TRUE)
      ->getGeneratedUrl() : NULL;
    return new static($entity, $action, $url);
  }

  /**
   * Get the entity for the action.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Sets the entity for the action.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *   The event entity.
   */
  public function setEntity(EntityInterface $entity): EntityRevalidateEventInterface {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAction(): string {
    return $this->action;
  }

  /**
   * {@inheritdoc}
   */
  public function setAction(string $action): EntityRevalidateEventInterface {
    $this->action = $action;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityUrl(): ?string {
    return $this->entityUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityUrl(string $url): EntityRevalidateEventInterface {
    $this->entityUrl = $url;
    return $this;
  }

}
