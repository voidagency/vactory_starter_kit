<?php

namespace Drupal\vactory_academy\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;

/**
 * Use for Dynamic Field field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_flag",
 *   label = @Translation("Vactory Flag"),
 *   description = @Translation("Flag data.")
 * )
 */
class VactoryFLagEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {


  protected $cacheability;
  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FlagServiceInterface $flag) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flagService = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {

    /** @var \Drupal\Core\Cache\CacheableMetadata $cacheability */
    $cacheability = (object) $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY];
    $this->cacheability = $cacheability;
    $this->cacheability->addCacheContexts(['user']);
    $this->cacheability->addCacheTags(['flagging_list']);
    $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY] = $this->cacheability;
    return $data;
  }

  /**
   * Apply formatters such as processed_text, image & links.
   *
   * @param array $parent_keys
   *   Keys.
   * @param array $settings
   *   Settings.
   * @param array $component
   *   Component.
   */

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
      'type' => 'boolean',
    ];
  }

}
