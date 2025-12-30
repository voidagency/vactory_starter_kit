<?php

namespace Drupal\vactory_dynamic_field_volatile\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A DF provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_dynamic_field_volatile",
 *   title = @Translation("Vactory Dynamic Field Volatile")
 * )
 */
class VactoryDynamicFieldVolatile extends VactoryDynamicFieldPluginBase {

  /**
   * Extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    $uri = 'sites/default/private/volatile-df';
    if (!file_exists($uri)) {
      mkdir($uri);
    }
    $instance->setWidgetsPath($uri);
    return $instance;
  }

}
