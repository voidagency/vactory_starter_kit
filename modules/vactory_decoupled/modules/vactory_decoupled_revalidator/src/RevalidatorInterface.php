<?php

namespace Drupal\vactory_decoupled_revalidator;

use Drupal\vactory_decoupled_revalidator\Event\EntityRevalidateEventInterface;

/**
 * Interface for revalidator plugins.
 */
interface RevalidatorInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function getId();

  /**
   * {@inheritdoc}
   */
  public function getLabel();
  /**
   * {@inheritdoc}
   */
  public function getDescription();

  /**
   * Revalidates an entity.
   *
   * @return bool
   *   TRUE if the entity was revalidated. FALSE otherwise.
   */
  public function revalidate(EntityRevalidateEventInterface $event);


}
