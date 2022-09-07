<?php

namespace Drupal\vactory_splashscreen\Plugin\vactory_dynamic_field\Platform;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A DF provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_splashscreen",
 *   title = @Translation("Splash Screen")
 * )
 */
class VactorySplashScreen extends VactoryDynamicFieldPluginBase {

  /**
   * Extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * {@inheritDoc }
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    $instance->setWidgetsPath($instance->extensionPathResolver->getPath('module', 'vactory_splashscreen') . '/widgets');
    return $instance;
  }

}
