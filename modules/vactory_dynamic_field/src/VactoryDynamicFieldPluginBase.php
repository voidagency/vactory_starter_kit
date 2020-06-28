<?php

namespace Drupal\vactory_dynamic_field;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base for the provider plugins.
 */
abstract class VactoryDynamicFieldPluginBase extends PluginBase implements VactoryDynamicFieldPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Path to the module.
   *
   * @var string
   */
  public $widgetsPath;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $widgetsPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->widgetsPath = $widgetsPath;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      ''
    );
  }

  /**
   * @return string
   */
  public function getWidgetsPath() {
    return $this->widgetsPath;
  }

  /**
   * @param string $widgetsPath
   */
  public function setWidgetsPath($widgetsPath) {
    $this->widgetsPath = $widgetsPath;
  }

}
