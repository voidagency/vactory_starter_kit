<?php

namespace Drupal\vactory_decoupled\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\vactory_decoupled\DynamicFieldManager;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for Dynamic Field field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_dynamic_field",
 *   label = @Translation("Vactory Dynamic Field"),
 *   description = @Translation("Unserialize dynamic field data.")
 * )
 */
class VactoryDynamicFieldEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * Dynamic field manager service.
   *
   * @var \Drupal\vactory_decoupled\DynamicFieldManager
   */
  protected $dynamicFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, DynamicFieldManager $dynamicFieldManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dynamicFieldManager = $dynamicFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('vactory_decoupled.dynamic_field_manager'));
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    return $this->dynamicFieldManager->transform($data, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
    ];
  }

}
