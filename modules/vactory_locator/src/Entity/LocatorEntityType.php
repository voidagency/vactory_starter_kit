<?php

namespace Drupal\vactory_locator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Locator Entity type entity.
 *
 * @ConfigEntityType(
 *   id = "locator_entity_type",
 *   label = @Translation("Locator Entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vactory_locator\LocatorEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vactory_locator\Form\LocatorEntityTypeForm",
 *       "edit" = "Drupal\vactory_locator\Form\LocatorEntityTypeForm",
 *       "delete" = "Drupal\vactory_locator\Form\LocatorEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vactory_locator\LocatorEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "locator_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "locator_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/locator_entity/conf/locator_entity_type/{locator_entity_type}",
 *     "add-form" = "/admin/structure/locator_entity/conf/locator_entity_type/add",
 *     "edit-form" = "/admin/structure/locator_entity/conf/locator_entity_type/{locator_entity_type}/edit",
 *     "delete-form" = "/admin/structure/locator_entity/conf/locator_entity_type/{locator_entity_type}/delete",
 *     "collection" = "/admin/structure/locator_entity/conf/locator_entity_type"
 *   }
 * )
 */
class LocatorEntityType extends ConfigEntityBundleBase implements LocatorEntityTypeInterface {

  /**
   * The Locator Entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Locator Entity type label.
   *
   * @var string
   */
  protected $label;

}
