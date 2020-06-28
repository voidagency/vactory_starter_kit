<?php

namespace Drupal\vactory_locator\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Locator Entity entities.
 *
 * @ingroup vactory_locator
 */
interface LocatorEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Locator Entity name.
   *
   * @return string
   *   Name of the Locator Entity.
   */
  public function getName();

  /**
   * Sets the Locator Entity name.
   *
   * @param string $name
   *   The Locator Entity name.
   *
   * @return \Drupal\vactory_locator\Entity\LocatorEntityInterface
   *   The called Locator Entity entity.
   */
  public function setName($name);

  /**
   * Gets the Locator Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Locator Entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Locator Entity creation timestamp.
   *
   * @param int $timestamp
   *   The Locator Entity creation timestamp.
   *
   * @return \Drupal\vactory_locator\Entity\LocatorEntityInterface
   *   The called Locator Entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Locator Entity published status indicator.
   *
   * Unpublished Locator Entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Locator Entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Locator Entity.
   *
   * @param bool $published
   *   TRUE to set this Locator Entity to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\vactory_locator\Entity\LocatorEntityInterface
   *   The called Locator Entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the Locator Entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Locator Entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\vactory_locator\Entity\LocatorEntityInterface
   *   The called Locator Entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Locator Entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Locator Entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\vactory_locator\Entity\LocatorEntityInterface
   *   The called Locator Entity entity.
   */
  public function setRevisionUserId($uid);

}
