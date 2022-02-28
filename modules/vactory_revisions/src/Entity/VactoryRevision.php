<?php

namespace Drupal\vactory_revisions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Vactory revision entity.
 *
 * @ingroup vactory
 *
 * @ContentEntityType(
 *   id = "vactory_revision",
 *   label = @Translation("Vactory revision"),
 *   base_table = "vactory_revisions",
 *   handlers = {
 *     "views_data" = "Drupal\vactory_revisions\Entity\VactoryRevisionViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class VactoryRevision extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Advertiser entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Advertiser entity.'))
      ->setReadOnly(TRUE);

    $fields['op_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Operation type'))
      ->setDescription(t('The operation type.'))
      ->setRequired(TRUE);

    $fields['op_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Operation time'))
      ->setDescription(t('The operation time.'))
      ->setRequired(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The concerned entity ID.'))
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The concerned entity type.'))
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('User id'))
      ->setDescription(t('The user id.'))
      ->setRequired(TRUE);

    return $fields;
  }

}