<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

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
      $definition->setClass('Drupal\vactory_decoupled\SimpleOauthAuthenticationProvider')
        ->addArgument(new Reference('simple_oauth.server.resource_server'))
        ->addArgument(new Reference('entity_type.manager'))
        ->addArgument(new Reference('simple_oauth.page_cache_request_policy.disallow_oauth2_token_requests'));
    }
  }
}
