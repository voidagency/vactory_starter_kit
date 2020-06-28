<?php

namespace Drupal\vactory_dynamic_field\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the VactoryDynamicField entity.
 *
 * @ingroup VactoryDynamicField
 *
 * @ContentEntityType(
 *   id = "vactory_dynamic_field",
 *   label = @Translation("Dynamic Field"),
 *   base_table = "vactory_dynamic_field",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "access" = "Drupal\vactory_dynamic_field\VactoryDynamicFieldEntityAccessControlHandler",
 *   },
 *
 * )
 */
class VactoryDynamicField extends ContentEntityBase implements ContentEntityInterface {

  /**
   * BaseFieldDefinitions function.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type.
   *
   * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]|mixed
   *   Fields array.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Advertiser entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Advertiser entity.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
