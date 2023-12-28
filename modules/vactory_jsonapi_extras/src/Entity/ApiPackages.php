<?php

namespace Drupal\vactory_jsonapi_extras\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\vactory_jsonapi_extras\ApiPackagesInterface;

/**
 * Defines the json:api packages entity type.
 *
 * @ConfigEntityType(
 *   id = "api_package",
 *   label = @Translation("API Packages"),
 *   label_collection = @Translation("API packages"),
 *   label_singular = @Translation("API package"),
 *   label_plural = @Translation("API packages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count API package",
 *     plural = "@count API packages",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\vactory_jsonapi_extras\ApiPackagesListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vactory_jsonapi_extras\Form\ApiPackagesForm",
 *       "edit" = "Drupal\vactory_jsonapi_extras\Form\ApiPackagesForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "apis_packages",
 *   admin_permission = "administer api packages",
 *   links = {
 *     "collection" = "/admin/structure/apis-packages",
 *     "add-form" = "/admin/structure/apis-packages/add",
 *     "edit-form" = "/admin/structure/apis-packages/{api_package}",
 *     "delete-form" = "/admin/structure/apis-packages/{api_package}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "roles"
 *   }
 * )
 */
class ApiPackages extends ConfigEntityBase implements ApiPackagesInterface {

  /**
   * The json:api packages ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The json:api packages label.
   *
   * @var string
   */
  protected $label;

  /**
   * The api_packages roles.
   *
   * @var array
   */
  protected $roles;

  public function roles() {
    return $this->roles;
  }

  public function setRoles(array $roles) {
    return $this->roles = $roles;
  }

}
