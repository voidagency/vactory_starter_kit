<?php

namespace Drupal\vactory_jsonapi_extras\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\vactory_jsonapi_extras\ExposedApisInterface;
use Drupal\filter\Annotation\Filter;

/**
 * Defines exposed apis entity type.
 *
 * @ConfigEntityType(
 *   id = "exposed_apis",
 *   label = @Translation("Exposed APIs"),
 *   label_collection = @Translation("Exposed APIses"),
 *   label_singular = @Translation("Exposed api"),
 *   label_plural = @Translation("Exposed apis"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Exposed api",
 *     plural = "@count Exposed apis",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\vactory_jsonapi_extras\ExposedApisListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vactory_jsonapi_extras\Form\ExposedApisForm",
 *       "edit" = "Drupal\vactory_jsonapi_extras\Form\ExposedApisForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "exposed_apis",
 *   admin_permission = "administer exposed apis",
 *   links = {
 *     "collection" = "/admin/structure/exposed-apis",
 *     "add-form" = "/admin/structure/exposed-apis/add",
 *     "edit-form" = "/admin/structure/exposed-apis/{exposed_apis}",
 *     "delete-form" = "/admin/structure/exposed-apis/{exposed_apis}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "path" = "path",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "original_resource",
 *     "is_custom_resource",
 *     "search_api_resource",
 *     "is_search_api_resource",
 *     "is_jsonapi_include",
 *     "custom_controller",
 *     "packages",
 *     "path",
 *     "filters",
 *     "includes",
 *     "fields",
 *   }
 * )
 */
class ExposedApis extends ConfigEntityBase implements ExposedApisInterface {

  /**
   * The exposed apis ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The exposed apis label.
   *
   * @var string
   */
  protected $label;

  /**
   * Original jsonapi resource.
   *
   * @var string
   */
  protected $path;

  /**
   * The exposed apis status.
   *
   * @var bool
   */
  protected $status;

  /**
   * Original jsonapi resource.
   *
   * @var string
   */
  protected $original_resource;

  /**
   * Search jsonapi resource.
   *
   * @var string
   */
  protected $search_api_resource;

  /**
   * JsonApi include.
   *
   * @var string
   */
  protected $is_jsonapi_include;

  /**
   * Default filters.
   *
   * @var string
   */
  protected $filters;

  /**
   * Default includes.
   *
   * @var string
   */
  protected $includes;

  /**
   * Default fields.
   *
   * @var string
   */
  protected $fields;

  /**
   * Route type.
   *
   * @var boolean
   */
  protected $is_custom_resource;

  /**
   * Search api resource type.
   *
   * @var boolean
   */
  protected $is_search_api_resource;

  /**
   * Custom controller.
   *
   * @var string
   */
  protected $custom_controller;

  /**
   * Route packages.
   *
   * @var array
   */
  protected $packages;

  /**
   * Path getter.
   *
   * @return string
   */
  public function path() {
    return $this->path;
  }

  /**
   * Roles getter.
   *
   * @return array
   */
  public function packages() {
    return $this->packages;
  }

  /**
   * Original resource getter.
   *
   * @return string
   */
  public function originalResource() {
    return $this->original_resource;
  }

  /**
   * @return string
   */
  public function getFilters(): string {
    return $this->filters ?? '';
  }

  /**
   * @return string
   */
  public function getFields(): string {
    return $this->fields ?? '';
  }

  /**
   * @return string
   */
  public function getIncludes(): string {
    return $this->includes ?? '';
  }

  /**
   * @return string
   */
  public function getCustomController(): string {
    return $this->custom_controller ?? '';
  }

  /**
   * Search jsonapi resource getter.
   *
   * @return string
   */
  public function searchJsonapiResource() {
    return $this->search_api_resource;
  }

  /**
   * Search jsonapi resource getter.
   *
   * @return string
   */
  public function isSearchApiResource() {
    return $this->is_search_api_resource;
  }

  /**
   * Search jsonapi resource getter.
   *
   * @return string
   */
  public function isJsonApiInclude() {
    return $this->is_jsonapi_include;
  }

  /**
   * @return string
   */
  public function isCustomResource(): string {
    return $this->is_custom_resource ?? '';
  }

}
