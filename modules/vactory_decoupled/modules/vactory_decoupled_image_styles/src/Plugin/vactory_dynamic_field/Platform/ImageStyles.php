<?php

namespace Drupal\vactory_decoupled_image_styles\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom DFs provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_decoupled_image_styles",
 *   title = @Translation("Vactory decoupled image styles")
 * )
 */
class ImageStyles extends VactoryDynamicFieldPluginBase {

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
    $instance->setWidgetsPath($instance->extensionPathResolver->getPath('module', 'vactory_decoupled_image_styles') . '/widgets');
    return $instance;
  }

}
