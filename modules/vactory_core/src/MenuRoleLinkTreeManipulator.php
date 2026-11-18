<?php

namespace Drupal\vactory_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * Menu link tree manipulator that hides links restricted to other roles.
 *
 * @package Drupal\vactory_core
 */
class MenuRoleLinkTreeManipulator extends DefaultMenuLinkTreeManipulators {

  /**
   * Admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Injects the admin context service.
   *
   * Setter injection (see VactoryCoreServiceProvider): the core constructor
   * arguments are left untouched.
   */
  public function setAdminContext(AdminContext $adminContext): void {
    $this->adminContext = $adminContext;
  }

  /**
   * {@inheritdoc}
   */
  protected function menuLinkCheckAccess(MenuLinkInterface $instance) {
    $result = parent::menuLinkCheckAccess($instance);
    if ($instance instanceof MenuLinkContent) {
      // Sadly ::getEntity() is protected at the moment.
      $function = function () {
        return $this->getEntity();
      };
      $function = \Closure::bind($function, $instance, get_class($instance));
      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
      $entity = $function();
      if (isset($entity->menu_role_show_role)) {

        $show_role = $entity->menu_role_show_role->getValue();
        $show_role = array_column($show_role, 'target_id');

        // Check whether this role has visibility access (must be present).
        if ($show_role && count(array_intersect($show_role, $this->account->getRoles())) == 0 && !$this->adminContext->isAdminRoute()) {
          $result = $result->andIf(AccessResult::forbidden()
            ->addCacheContexts(['user.roles']));
        }
      }

    }
    return $result;
  }

}
