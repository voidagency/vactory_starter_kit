<?php

namespace Drupal\vactory_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

// @note: You only need Reference, if you want to change service arguments.
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
    $definition->setClass('Drupal\vactory_core\MenuItems')
      ->addArgument(new Reference('menu.link_tree'));
    // Override menu.default_tree_manipulators service.
    $container->getDefinition('menu.default_tree_manipulators')
      ->setClass(MenuRoleLinkTreeManipulator::class);
  }

}
