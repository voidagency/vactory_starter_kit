<?php

namespace Drupal\vactory_calendar\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Calendar slot entity.
 *
 * @ingroup vactory_calendar
 *
 * @ContentEntityType(
 *   id = "calendar_slot",
 *   label = @Translation("Calendar slot"),
 *   bundle_label = @Translation("Calendar slot type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vactory_calendar\CalendarSlotListBuilder",
 *     "views_data" = "Drupal\vactory_calendar\Entity\CalendarSlotViewsData",
 *     "translation" = "Drupal\vactory_calendar\CalendarSlotTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\vactory_calendar\Form\CalendarSlotForm",
 *       "add" = "Drupal\vactory_calendar\Form\CalendarSlotForm",
 *       "edit" = "Drupal\vactory_calendar\Form\CalendarSlotForm",
 *       "delete" = "Drupal\vactory_calendar\Form\CalendarSlotDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vactory_calendar\CalendarSlotHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\vactory_calendar\CalendarSlotAccessControlHandler",
 *   },
 *   base_table = "calendar_slot",
 *   data_table = "calendar_slot_field_data",
 *   translatable = TRUE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer calendar slot entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/calendar_slot/{calendar_slot}",
 *     "add-page" = "/admin/structure/calendar_slot/add",
 *     "add-form" = "/admin/structure/calendar_slot/add/{calendar_slot_type}",
 *     "edit-form" = "/admin/structure/calendar_slot/{calendar_slot}/edit",
 *     "delete-form" = "/admin/structure/calendar_slot/{calendar_slot}/delete",
 *     "collection" = "/admin/structure/calendar_slot",
 *   },
 *   bundle_entity_type = "calendar_slot_type",
 *   field_ui_base_route = "entity.calendar_slot_type.edit_form"
 * )
 */
class CalendarSlot extends ContentEntityBase implements CalendarSlotInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user ID of the owner of the Calendar slot.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invited_user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Invited'))
      ->setDescription(t('Invited User Ids'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Calendar slot.'))
      ->setSettings([
        'max_length' => 240,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Calendar slot is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 7,
      ]);

    $fields['etat'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t("Statut de l'invitation"))
      ->setDescription(t('A variable indicating the status of the Calendar slot'))
      ->setDefaultValue('pending')
    ->setSettings([
        'allowed_values' => [
          'confirmed' => t('Confirmé'),
          'pending' => t('En attente'),
          'declined' => t('Rejeté'),
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'list_default',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $current_date_time = DrupalDateTime::createFromTimestamp(time())
      ->format('Y-m-d\TH:i:s');
    $fields['start_time'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('When'))
      ->setDefaultValue($current_date_time)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['end_time'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Until'))
      ->setDefaultValue($current_date_time)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
    return $fields;
  }

}
