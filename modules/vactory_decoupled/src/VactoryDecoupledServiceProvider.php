<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alter services.
 */
class VactoryDecoupledServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Alter simple oauth authentication service.
    if ($container->hasDefinition('simple_oauth.authentication.simple_oauth')) {
      $definition = $container->getDefinition('simple_oauth.authentication.simple_oauth');
      $definition->setClass('Drupal\vactory_decoupled\SimpleOauthAuthenticationProvider');
    }
  }

}
