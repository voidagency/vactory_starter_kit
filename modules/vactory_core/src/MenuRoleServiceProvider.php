<?php

namespace Drupal\vactory_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * ServiceModifier implementation.
 *
 * @package Drupal\menu_per_role
 */
class MenuRoleServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('menu.default_tree_manipulators')
      ->setClass(MenuRoleLinkTreeManipulator::class);
  }

}
