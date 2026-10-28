<?php

namespace Drupal\vactory_phone_auth_decoupled;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\vactory_phone_auth_decoupled\Repositories\UserRepository;

/**
 * Class lld core service provider.
 */
class VactoryPhoneAuthDecoupledServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $phone_auth = \Drupal::config('vactory_phone_auth_decoupled.settings')->get('phone_auth_enable');
    if (isset($phone_auth) && $phone_auth === 1 && $container->hasDefinition('simple_oauth_password_grant.repositories.user')) {
      $container->getDefinition('simple_oauth_password_grant.repositories.user')
        ->setClass(UserRepository::class);
    }
  }

}
