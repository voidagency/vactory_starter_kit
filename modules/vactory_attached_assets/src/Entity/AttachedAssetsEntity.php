<?php

namespace Drupal\vactory_attached_assets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Attached assets entity.
 *
 * @ConfigEntityType(
 *   id = "attached_assets_entity",
 *   label = @Translation("Attached assets"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vactory_attached_assets\AttachedAssetsEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vactory_attached_assets\Form\AttachedAssetsEntityForm",
 *       "edit" = "Drupal\vactory_attached_assets\Form\AttachedAssetsEntityForm",
 *       "delete" = "Drupal\vactory_attached_assets\Form\AttachedAssetsEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vactory_attached_assets\AttachedAssetsEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "attached_assets_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/attached_assets_entity/{attached_assets_entity}",
 *     "add-form" = "/admin/structure/attached_assets_entity/add",
 *     "edit-form" = "/admin/structure/attached_assets_entity/{attached_assets_entity}/edit",
 *     "delete-form" = "/admin/structure/attached_assets_entity/{attached_assets_entity}/delete",
 *     "collection" = "/admin/structure/attached_assets_entity"
 *   }
 * )
 */
class AttachedAssetsEntity extends ConfigEntityBase implements AttachedAssetsEntityInterface {

  /**
   * The Attached assets ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Attached assets label.
   *
   * @var string
   */
  protected $label;

  /**
   * The asset file id.
   *
   * @var int
   */
  protected $file;

  /**
   * The asset type (style or script).
   *
   * @var string
   */
  protected $type;

  /**
   * The insertion conditions.
   *
   * Each item is the configuration array.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * {@inheritdoc}
   */
  public function setConditions($condition_id, $condition) {
    $this->conditions[$condition_id] = $condition;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() : array {
    return $this->conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileId() {
    return $this->file;
  }

  /**
   * {@inheritdoc}
   */
  public function setFileId($fid) {
    $this->file = $fid;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
  }

}
