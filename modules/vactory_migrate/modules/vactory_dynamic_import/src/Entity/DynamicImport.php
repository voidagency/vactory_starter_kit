<?php

namespace Drupal\vactory_dynamic_import\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\vactory_dynamic_import\DynamicImportInterface;

/**
 * Defines the Dynamic import entity.
 *
 * @ConfigEntityType(
 *   id = "dynamic_import",
 *   label = @Translation("Dynamic import"),
 *   handlers = {
 *     "list_builder" = "Drupal\vactory_dynamic_import\Controller\VactoryDynamicImportListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vactory_dynamic_import\Form\DynamicImportForm",
 *       "edit" = "Drupal\vactory_dynamic_import\Form\DynamicImportForm",
 *       "delete" = "Drupal\vactory_dynamic_import\Form\DynamicImportDeleteForm",
 *     }
 *   },
 *   config_prefix = "dynamic_import",
 *   admin_permission = "administer dynamic imports",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "target_entity" = "target_entity",
 *     "target_bundle" = "target_bundle",
 *     "concered_fields" = "concered_fields",
 *     "is_translation" = "is_translation",
 *     "translation_langcode" = "translation_langcode"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "target_entity",
 *     "target_bundle",
 *     "concered_fields",
 *     "is_translation",
 *     "translation_langcode",
 *
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/dynamic_import/{dynamic_import}",
 *     "delete-form" = "/admin/config/system/dynamic_import/{dynamic_import}/delete",
 *   }
 * )
 */
class DynamicImport extends ConfigEntityBase implements DynamicImportInterface {

  /**
   * The dynamic import machine name.
   *
   * @var string
   */
  protected string $id;

  /**
   * The dynamic import label.
   *
   * @var string
   */
  protected string $label;

  /**
   * The dynamic import target entity.
   *
   * @var string
   */
  protected string $target_entity;

  /**
   * The dynamic import target bundle.
   *
   * @var string
   */
  protected string $target_bundle;

  /**
   * The dynamic import fields.
   *
   * @var array
   * todo fix typo
   */
  protected array $concered_fields;

  /**
   * is translation import.
   *
   * @var boolean
   */
  protected bool $is_translation;

  /**
   * The dynamic import fields.
   *
   * @var string
   */
  protected string $translation_langcode;

  // Your specific configuration property get/set methods go here,
  // implementing the interface.
}