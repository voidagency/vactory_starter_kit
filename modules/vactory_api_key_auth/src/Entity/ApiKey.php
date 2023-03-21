<?php

namespace Drupal\vactory_api_key_auth\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\vactory_api_key_auth\ApiKeyInterface;

/**
 * Defines the Api key entity.
 *
 * @ConfigEntityType(
 *   id = "api_key",
 *   label = @Translation("Api key"),
 *   handlers = {
 *     "list_builder" = "Drupal\vactory_api_key_auth\Controller\ApiKeyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\vactory_api_key_auth\Form\ApiKeyForm",
 *       "edit" = "Drupal\vactory_api_key_auth\Form\ApiKeyForm",
 *       "delete" = "Drupal\vactory_api_key_auth\Form\ApiKeyDeleteConfirmForm",
 *     },
 *   },
 *   config_prefix = "api_key",
 *   admin_permission = "administer vactory_api_key_auth",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "key" = "key"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/api_key",
 *     "edit-form" = "/admin/config/services/api_key/{api_key}/edit",
 *     "delete-form" = "/admin/config/services/api_key/{api_key}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "key",
 *     "user_uuid",
 *   }
 * )
 */
class ApiKey extends ConfigEntityBase implements ApiKeyInterface {
  /**
   * The Api key ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Api key label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Api key.
   *
   * @var string
   */
  public $key;

  /**
   * The User UUID.
   *
   * @var string
   */
  public $user_uuid;

}
