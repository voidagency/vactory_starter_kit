<?php

namespace Drupal\vactory_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies simplify menu and menu default tree manipulators services.
 */
class VactoryCoreServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override simplify_menu_menu_items service.
    $definition = $container->getDefinition('simplify_menu.menu_items');
    // Our dependencies are set through a method call, not appended to the
    // parent's constructor arguments: a new upstream constructor argument
    // would otherwise shift them and break the whole container.
    $definition->setClass(MenuItems::class)
      ->addMethodCall('setVactoryServices', [
        new Reference('entity.repository'),
        new Reference('entity_field.manager'),
        new Reference('entity_type.manager'),
        new Reference('current_user'),
      ]);
    // Override menu.default_tree_manipulators service.
    $container->getDefinition('menu.default_tree_manipulators')
      ->setClass(MenuRoleLinkTreeManipulator::class)
      ->addMethodCall('setAdminContext', [new Reference('router.admin_context')]);
  }

}
