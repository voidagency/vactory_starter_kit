<?php

namespace Drupal\vactory_calendar\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Calendar slot type entity.
 *
 * @ConfigEntityType(
 *   id = "calendar_slot_type",
 *   label = @Translation("Calendar slot type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vactory_calendar\CalendarSlotTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vactory_calendar\Form\CalendarSlotTypeForm",
 *       "edit" = "Drupal\vactory_calendar\Form\CalendarSlotTypeForm",
 *       "delete" = "Drupal\vactory_calendar\Form\CalendarSlotTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vactory_calendar\CalendarSlotTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_export = {
 *     "id",
 *     "label"
 *   },
 *   config_prefix = "calendar_slot_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "calendar_slot",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/calendar_slot_type/{calendar_slot_type}",
 *     "add-form" = "/admin/structure/calendar_slot_type/add",
 *     "edit-form" = "/admin/structure/calendar_slot_type/{calendar_slot_type}/edit",
 *     "delete-form" = "/admin/structure/calendar_slot_type/{calendar_slot_type}/delete",
 *     "collection" = "/admin/structure/calendar_slot_type"
 *   }
 * )
 */
class CalendarSlotType extends ConfigEntityBundleBase implements CalendarSlotTypeInterface {

  /**
   * The Calendar slot type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Calendar slot type label.
   *
   * @var string
   */
  protected $label;

}
