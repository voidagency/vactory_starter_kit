<?php

namespace Drupal\vactory_attached_assets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Attached assets entities.
 */
interface AttachedAssetsEntityInterface extends ConfigEntityInterface {

  /**
   * Sets the Asset entity conditions.
   *
   * @param string $condition_id
   *   The condition machine name.
   * @param array $condition
   *   The condition configuration.
   */
  public function setConditions($condition_id, array $condition);

  /**
   * Gets the Asset entity conditions.
   */
  public function getConditions();

  /**
   * Sets the Asset entity file id.
   *
   * @param int $fid
   *   The file id.
   */
  public function setFileId($fid);

  /**
   * Gets the Asset entity file id.
   */
  public function getFileId();

  /**
   * Sets the Asset entity type.
   *
   * @param string $type
   *   The asset type (either style or script).
   */
  public function setType($type);

  /**
   * Gets the Asset entity type.
   */
  public function getType();

}
