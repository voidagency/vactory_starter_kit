<?php

namespace Drupal\vactory_decoupled_revalidator\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEvent;
use Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * vactory_decoupled_revalidator event subscriber.
 */
class VactoryDecoupledRevalidatorSubscriber implements EventSubscriberInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityRevalidateEvent::ENTITY_REVALIDATED_ACTION => ['onAction'],
    ];
  }

  /**
   * Revalidates the entity.
   */
  public function onAction(EntityRevalidateEventInterface $event) {
    if ($revalidator = $this->getRevalidator($event->getEntity())) {
      $revalidator->revalidate($event);
    }

    return NULL;
  }

  /**
   * Get revalidator.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getRevalidator(EntityInterface $entity) {
    $id = sprintf('%s.%s', $entity->getEntityTypeId(), $entity->bundle());
    $revalidator_entity_type_config = $this->entityTypeManager
      ->getStorage('revalidator_entity_type')
      ->load($id);
    if ($revalidator_entity_type_config) {
      return $revalidator_entity_type_config->getRevalidator();
    }
    return NULL;
  }

}
