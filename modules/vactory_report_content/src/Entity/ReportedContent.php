<?php

namespace Drupal\vactory_report_content\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the reported content entity.
 *
 * @ingroup ReportedContent
 *
 * @ContentEntityType(
 *   id = "reported_content",
 *   label = @Translation("Reported content"),
 *   base_table = "reported_content",
 *   admin_permission = "administer reported content",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vactory_report_content\Entity\ReportedContentListBuilder",
 *     "views_data" = "Drupal\vactory_report_content\Entity\ReportedContentViewsData",
 *     "form" = {
 *       "add" = "Drupal\vactory_report_content\Form\ReportedContentForm",
 *       "edit" = "Drupal\vactory_report_content\Form\ReportedContentForm",
 *       "delete" = "Drupal\vactory_report_content\Form\ReportedContentDeleteForm",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/admin/reported-content/{notifications_entity}",
 *     "edit-form" = "/admin/reported-content/{notifications_entity}/edit",
 *     "delete-form" = "/admin/reported-content/{notifications_entity}/delete",
 *     "collection" = "/admin/reported-content"
 *   },
 * )
 */
class ReportedContent extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(\Drupal\Core\Entity\EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Advertiser entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Advertiser entity.'))
      ->setReadOnly(TRUE);

    $fields['page'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Page path'))
      ->setDescription(t('The reported page path'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ]);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The report description'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The date of reporting.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 2,
      ]);

    $fields['reason'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reason'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['reported_content_reasons' => 'reported_content_reasons']])
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Status'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['reported_content_status' => 'reported_content_status']])
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reporter'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reporter'))
      ->setDescription(t('The Name of the reporter user.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ),
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
