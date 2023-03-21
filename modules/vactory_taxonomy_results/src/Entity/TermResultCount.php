<?php

namespace Drupal\vactory_taxonomy_results\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\vactory_taxonomy_results\TermResultCountInterface;

/**
 * Defines the termresultscount entity class.
 *
 * @ContentEntityType(
 *   id = "term_result_count",
 *   label = @Translation("TermResultsCount"),
 *   label_collection = @Translation("TermResultsCounts"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vactory_taxonomy_results\TermResultCountListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\vactory_taxonomy_results\Entity\TermResultCountAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "term_result_count",
 *   data_table = "term_result_count_field_data",
 *   translatable = TRUE,
 *   admin_permission = "access termresultscount overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/term-result-count/add",
 *     "canonical" = "/term_result_count/{term_result_count}",
 *     "edit-form" = "/admin/content/term-result-count/{term_result_count}/edit",
 *     "delete-form" = "/admin/content/term-result-count/{term_result_count}/delete",
 *     "collection" = "/admin/content/term-result-count"
 *   },
 * )
 */
class TermResultCount extends ContentEntityBase implements TermResultCountInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['tid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Term id'))
      ->setDescription(t('Term id'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);
    $fields['count'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Count'))
      ->setDescription(t('Count'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ]);
    $fields['plugin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin'))
      ->setDescription(t('Plugin'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('Entity Type'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);
    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bunlde'))
      ->setDescription(t('Bunlde'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }

}
