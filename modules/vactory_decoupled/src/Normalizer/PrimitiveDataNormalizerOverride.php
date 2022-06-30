<?php

namespace Drupal\vactory_decoupled\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\serialization\Normalizer\PrimitiveDataNormalizer;

/**
 * Decorates core serialization primitive data normalizer service.
 */
class PrimitiveDataNormalizerOverride extends PrimitiveDataNormalizer {

  /**
   * Primitive data normalizer service.
   * 
   * @var \Drupal\serialization\Normalizer\PrimitiveDataNormalizer
   */
   protected $primitiveDataNormalizer;

  /**
   * {@inheritDoc}
   */
   public function __construct(PrimitiveDataNormalizer $primitiveDataNormalizer) {
     $this->primitiveDataNormalizer = $primitiveDataNormalizer;
   }

  /**
   * {@inheritDoc}
   */
   public function normalize($object, $format = NULL, array $context = []) {
     $route_name = \Drupal::routeMatch()->getRouteName();
     if (preg_match('#jsonapi\.(.)*\.individual$#', $route_name)) {
       if (isset($context['cacheability']) && $context['cacheability'] instanceof CacheableMetadata) {
         $context['cacheability']->addCacheTags(['node_list']);
         $this->addCacheableDependency($context, $object);
       }
     }
     return parent::normalize($object, $format, $context);
   }

}
