<?php

namespace Drupal\vactory_decoupled\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;

/**
 * Use for internal blocks field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_blocks",
 *   label = @Translation("Vactory Blocks"),
 *   description = @Translation("Use for internal_blocks field.")
 * )
 */
class VactoryBlocksFieldEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface
{

  /**
   * Language Id.
   *
   * @var string
   */
  protected $language;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // $this->language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context)
  {
    /** @var \Drupal\Core\Cache\CacheableMetadata $cacheability */
    $cacheability = (object) $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY];
    $blocks = $data ?? [];
    foreach ($blocks as $block) {
        $cacheability->addCacheTags($block['block_cache']['cache-tags']);
        $cacheability->addCacheContexts($block['block_cache']['contexts']);
    }

    $cacheability->addCacheTags(['config:block_list']);
    $cacheability->addCacheContexts(['url.query_args:q']);

    $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY] = $cacheability;

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context)
  {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema()
  {
    return [
        'type' => 'array',
      ];
  }

}
