<?php

namespace Drupal\vactory_dynamic_block\Plugin\vactory_dynamic_field\Platform;

use Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vactory Dynamic Block provider plugin.
 *
 * @PlatformProvider(
 *   id = "vactory_dynamic_block",
 *   title = @Translation("Vactory Dynamic Block")
 * )
 */
class VactoryDynamicBlock extends VactoryDynamicFieldPluginBase {

  /**
   * Extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    $instance->setWidgetsPath($instance->extensionPathResolver->getPath('module', 'vactory_dynamic_block') . '/widgets');
    return $instance;
  }

}
