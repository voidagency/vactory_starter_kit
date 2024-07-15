<?php

namespace Drupal\vactory_content_access\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class for managing vactory content Access.
 *
 * @package Drupal\vactory_content_access\Services
 */
class VactoryNodeAccessCheck {

  /**
   * The current user ID.
   *
   * @var string
   */
  protected $currentUserId;

  /**
   * Current user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $currentUserObject;

  /**
   * Entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The vactory node access check construct.
   */
  public function __construct(AccountProxy $account, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $module_handler) {
    $this->currentUserId = $account->id();
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $module_handler;
    $this->currentUserObject = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUserId);
  }

  /**
   * Check if the given node is accessible or not.
   */
  public function isAccessible(Node $node, $bundleFilter, $fieldUserGroupName, $fieldExtraUsersName, $fieldAccessByRolesName = NULL, User $user = NULL) {
    if (in_array($node->bundle(), $bundleFilter)) {
      if (isset($user)) {
        $this->currentUserObject = $user;
        $this->currentUserId = $user->id();
      }
      $is_accessible = FALSE;
      $current_user_id = $this->currentUserId;
      $current_user_groups = $this->currentUserObject->get('field_user_groups')
        ->getValue();
      $node_groups = $node->get($fieldUserGroupName)->getValue();
      // Grant access to the user having uid = 1.
      if ($current_user_id === '1') {
        $is_accessible = TRUE;
        return $is_accessible;
      }
      // Check if accessible using user group field.
      if (!empty($node_groups) && !empty($current_user_groups)) {
        $current_user_groups = array_map(function ($el) {
          return $el['target_id'];
        }, $current_user_groups);
        $node_groups = array_map(function ($el) {
          return $el['target_id'];
        }, $node_groups);
        if (count(array_intersect($current_user_groups, $node_groups)) > 0) {
          $is_accessible = TRUE;
        }
      }
      // Check if accessible using extra user field.
      if (!$is_accessible) {
        $extra_users = $node->get($fieldExtraUsersName)->getValue();
        if (!empty($extra_users)) {
          $extra_users = array_map(function ($el) {
            return $el['target_id'];
          }, $extra_users);
          if (in_array($current_user_id, $extra_users)) {
            $is_accessible = TRUE;
          }
        }
      }

      // Check if accessible by role.
      if (!$is_accessible && $fieldAccessByRolesName) {
        $current_user_roles = $this->currentUserObject->getRoles();
        $node_allowed_roles = $node->get($fieldAccessByRolesName)->getValue();
        // Grant access to all users if the node allowed roles are not defined.
        if (empty($node_allowed_roles) && empty($extra_users) && empty($node_groups)) {
          $is_accessible = TRUE;
        }
        if (!empty($node_allowed_roles)) {
          $is_negate_roles = $node->get("field_content_access_negate_roles")->value === "1";
          $node_allowed_roles = array_map(fn($el) => $el['target_id'], $node_allowed_roles);
          $role_intersect = count(array_intersect($current_user_roles, $node_allowed_roles)) > 0;

          if ($role_intersect) {
            $is_accessible = !$is_negate_roles;
          }
          else {
            $is_accessible = $is_negate_roles;
          }
        }
      }
      if (!empty($node->get('field_content_access_custom')->value)) {
        $key = $node->get('field_content_access_custom')->value;
        // Check if accessible by custom hook.
        $this->moduleHandler->alter('vactory_content_access', $is_accessible, $key, $node);
      }
      return $is_accessible;
    }

    return NULL;
  }

}
