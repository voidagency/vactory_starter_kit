<?php

namespace Drupal\vactory_extended_seo\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Vactory extended seo entities.
 *
 * @ingroup vactory_extended_seo
 */
interface VactoryExtendedSeoInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Vactory extended seo name.
   *
   * @return string
   *   Name of the Vactory extended seo.
   */
  public function getName();

  /**
   * Sets the Vactory extended seo name.
   *
   * @param string $name
   *   The Vactory extended seo name.
   *
   * @return \Drupal\vactory_extended_seo\Entity\VactoryExtendedSeoInterface
   *   The called Vactory extended seo entity.
   */
  public function setName($name);

  /**
   * Gets the Vactory extended seo creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Vactory extended seo.
   */
  public function getCreatedTime();

  /**
   * Sets the Vactory extended seo creation timestamp.
   *
   * @param int $timestamp
   *   The Vactory extended seo creation timestamp.
   *
   * @return \Drupal\vactory_extended_seo\Entity\VactoryExtendedSeoInterface
   *   The called Vactory extended seo entity.
   */
  public function setCreatedTime($timestamp);

}
