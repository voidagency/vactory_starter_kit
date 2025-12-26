<?php

declare(strict_types=1);

namespace Drupal\vactory_dynamic_block\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\vactory_dynamic_block\DynamicBlockInterface;

/**
 * Defines the dynamic block entity class.
 *
 * @ContentEntityType(
 *   id = "vactory_dynamic_block",
 *   label = @Translation("Dynamic Block"),
 *   label_collection = @Translation("Dynamic Blocks"),
 *   label_singular = @Translation("dynamic block"),
 *   label_plural = @Translation("dynamic blocks"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dynamic blocks",
 *     plural = "@count dynamic blocks",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\vactory_dynamic_block\DynamicBlockListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\vactory_dynamic_block\Form\DynamicBlockForm",
 *       "edit" = "Drupal\vactory_dynamic_block\Form\DynamicBlockForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vactory_dynamic_block\Routing\DynamicBlockHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "vactory_dynamic_block",
 *   data_table = "vactory_dynamic_block_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer vactory_dynamic_block",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/vactory-dynamic-block",
 *     "add-form" = "/vactory-dynamic-block/add",
 *     "canonical" = "/vactory-dynamic-block/{vactory_dynamic_block}",
 *     "edit-form" = "/vactory-dynamic-block/{vactory_dynamic_block}",
 *     "delete-form" = "/vactory-dynamic-block/{vactory_dynamic_block}/delete",
 *     "delete-multiple-form" = "/admin/content/vactory-dynamic-block/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.vactory_dynamic_block.settings",
 * )
 */
final class DynamicBlock extends ContentEntityBase implements DynamicBlockInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Type field can be: HTML or JSX, default is HTML.
    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the dynamic block.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'HTML' => 'HTML',
        'JSX' => 'JSX',
      ])
      ->setDefaultValue('HTML')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'list_default',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // String long field to store the dynamic block content (HTML/JSX).
    $fields['content'] = BaseFieldDefinition::create('string_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('Content'))
      ->setDescription(t('The content of the dynamic block.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -3,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['js_structure'] = BaseFieldDefinition::create('string_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('JavaScript'))
      ->setDescription(t('The JavaScript structure of the dynamic block.'))
      ->setRequired(FALSE);

    $fields['css'] = BaseFieldDefinition::create('string_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('CSS'))
      ->setDescription(t('The CSS of the dynamic block.'))
      ->setRequired(FALSE);

    $fields['block_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Block ID'))
      ->setDescription(t('Unique identifier for CSS scoping.'))
      ->setSetting('max_length', 12)
      ->setRequired(FALSE);

    return $fields;
  }

}
