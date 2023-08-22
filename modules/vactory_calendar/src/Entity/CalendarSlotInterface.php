<?php

namespace Drupal\vactory_calendar\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Calendar slot entities.
 *
 * @ingroup vactory_calendar
 */
interface CalendarSlotInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Calendar slot name.
   *
   * @return string
   *   Name of the Calendar slot.
   */
  public function getName();

  /**
   * Sets the Calendar slot name.
   *
   * @param string $name
   *   The Calendar slot name.
   *
   * @return \Drupal\vactory_calendar\Entity\CalendarSlotInterface
   *   The called Calendar slot entity.
   */
  public function setName($name);

  /**
   * Gets the Calendar slot creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Calendar slot.
   */
  public function getCreatedTime();

  /**
   * Sets the Calendar slot creation timestamp.
   *
   * @param int $timestamp
   *   The Calendar slot creation timestamp.
   *
   * @return \Drupal\vactory_calendar\Entity\CalendarSlotInterface
   *   The called Calendar slot entity.
   */
  public function setCreatedTime($timestamp);

}
