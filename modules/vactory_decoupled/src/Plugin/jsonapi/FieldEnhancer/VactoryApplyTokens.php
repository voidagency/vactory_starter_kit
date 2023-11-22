<?php

namespace Drupal\vactory_decoupled\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;

/**
 * Used for drupal tokens replacement.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_apply_tokens",
 *   label = @Translation("Apply Drupal tokens"),
 *   description = @Translation("Used to apply drupal tokens on field results")
 * )
 */
class VactoryApplyTokens extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    $object = $context['field_item_object'];
    $entity = $object->getEntity();
    if (!$entity instanceof EntityInterface) {
      return \Drupal::token()->replace($data);
    }
    $entity_type = $entity->getEntityTypeId();
    return \Drupal::token()->replace($data, [$entity_type => $entity]);
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
      'type' => 'string',
    ];
  }

}
