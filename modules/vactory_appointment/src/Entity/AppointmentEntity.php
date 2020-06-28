<?php

namespace Drupal\vactory_appointment\Entity;


use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;
use Drupal\webform\Plugin\WebformElement\DateTime;

/**
 * Class AppointmentEntity
 *
 * @ingroup vactory_appointment
 * @ContentEntityType(
 *   id = "vactory_appointment",
 *   label = @Translation("Appointment"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vactory_appointment\AppointmentsListBuilder",
 *     "views_data" = "Drupal\vactory_appointment\Entity\AppointmentsEntityViewsData",
 *     "translation" = "Drupal\vactory_appointment\AppointmentsEntityTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\vactory_appointment\Form\AppointmentsForm",
 *       "add" = "Drupal\vactory_appointment\Form\AppointmentsForm",
 *       "edit" = "Drupal\vactory_appointment\Form\AppointmentsForm",
 *       "delete" = "Drupal\vactory_appointment\Form\AppointmentsDeleteForm",
 *     },
 *     "access" = "Drupal\vactory_appointment\AppointmentsAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "vactory_appointment",
 *   data_table = "vactory_appointment_field_data",
 *   admin_permission = "administer appointments",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/vactory_appointment/{vactory_appointment}",
 *     "edit-form" = "/admin/structure/vactory_appointment/{vactory_appointment}/edit",
 *     "delete-form" = "/admin/structure/vactory_appointment/{vactory_appointment}/delete",
 *     "collection" = "/admin/structure/vactory_appointment/list",
 *   },
 *   field_ui_base_route = "vactory_appointment.appointments_settings",
 * )
 */
class AppointmentEntity extends ContentEntityBase implements AppointmentsEntityInterface {
  use EntityChangedTrait;

  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $config = \Drupal::configFactory()->get('vactory_appointment.settings');
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Appointment entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Appointment entity.'))
      ->setReadOnly(TRUE);

    // The appointment entity title.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Appointment entity.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The author ID of the Appointment entity.'))
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
        'weight' => 99,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    // The appointment adviser.
    $fields['adviser_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Appointment adviser'))
      ->setDescription(t('The Name of the associated adviser.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The appointment type.
    $fields['appointment_type'] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Appointment type'))
        ->setRevisionable(TRUE)
        ->setSetting('target_type', 'taxonomy_term')
        ->setSetting('handler_settings', ['target_bundles' => ['dam_motifs' => 'dam_motifs']])
        ->setSetting('handler', 'default')
        ->setTranslatable(TRUE)
        ->setDisplayOptions('view', array(
          'label' => 'hidden',
          'type' => 'author',
          'weight' => 0,
        ))
        ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 2,
        ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

    // The appointment Agency.
    $fields['appointment_agency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Appointment Agency'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['dam_agencies' => 'dam_agencies']])
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 2,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The appointment First name.
    $fields['appointment_first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 3,
      ))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The appointment Last name.
    $fields['appointment_last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 4,
      ))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The appointment Phone.
    $fields['appointment_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 5,
      ))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The appointment Email.
    $fields['appointment_email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 6,
      ))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    // The appointment day.
    $fields['appointment_day'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Day'))
      ->setDescription(t('The appointment day.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'datetime_type' => 'date'
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 7,
      ]);

    // The appointment hour.
    $fields['appointment_hour'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Appointment hour'))
      ->setDescription(t('The appointment hour.'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => $config->get('appointment_hours'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 8,
      ]);

    // Appointments status (is it published or not).
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Available'))
      ->setDescription(t('A boolean indicating whether the appointments is available yet.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 9,
      ]);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of Appointment entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Check if current appointment is available.
   *
   * @return boolean
   */
  public function isAvailable() {
    return (boolean) $this->get('status')->value;
  }

  /**
   * Get current appointment title.
   *
   * @return String
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * Set current appointment title.
   *
   * @param $title
   *
   * @return \Drupal\vactory_appointment\Entity\AppointmentEntity
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * Returns the entity owner's user ID.
   *
   * @return int|null
   *   The owner user ID, or NULL in case the user ID field has not been set on
   *   the entity.
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * Sets the entity owner's user ID.
   *
   * @param int $uid
   *   The owner user id.
   *
   * @return $this
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * Returns the entity owner's user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The owner user entity.
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * Sets the entity owner's user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The owner user entity.
   *
   * @return $this
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * Returns the appointment adviser.
   *
   * @return \Drupal\user\UserInterface
   *   The appointment adviser.
   */
  public function getAdviser() {
    return $this->get('adviser_id')->entity;
  }

  /**
   * Sets the appointment adviser.
   *
   * @param \Drupal\user\UserInterface $account
   *   The appointment adviser.
   *
   * @return $this
   */
  public function setAdviser(UserInterface $account) {
    $this->set('adviser_id', $account->id());
    return $this;
  }

  /**
   * Returns the appointment agency.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The appointment agency.
   */
  public function getAgency() {
    return $this->get('appointment_agency')->entity;
  }

  /**
   * Sets the appointment agency.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The appointment agency.
   *
   * @return $this
   */
  public function setAgency(Term $agency) {
    $this->set('appointment_agency', $agency->id());
    return $this;
  }

  /**
   * Returns the appointment type.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The appointment type.
   */
  public function getAppointmentType() {
    return $this->get('appointment_type')->entity;
  }

  /**
   * Sets the appointment type.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The appointment type.
   *
   * @return $this
   */
  public function setAppointmentType(Term $type) {
    $this->set('appointment_type', $type->id());
    return $this;
  }

  /**
   * Returns the appointment first name.
   *
   * @return string
   *   The appointment first name.
   */
  public function getAppointmentFirstName() {
    return $this->get('appointment_first_name')->value;
  }

  /**
   * Sets the appointment first name.
   *
   * @param string $first_name
   *   The appointment first name.
   *
   * @return $this
   */
  public function setAppointmentFirstName($first_name) {
    $this->set('appointment_first_name', $first_name);
    return $this;
  }

  /**
   * Returns the appointment last name.
   *
   * @return string
   *   The appointment last name.
   */
  public function getAppointmentLastName() {
    return $this->get('appointment_last_name')->value;
  }

  /**
   * Sets the appointment last name.
   *
   * @param string $last_name
   *   The appointment last name.
   *
   * @return $this
   */
  public function setAppointmentLastName($last_name) {
    $this->set('appointment_last_name', $last_name);
    return $this;
  }

  /**
   * Returns the appointment phone.
   *
   * @return string
   *   The appointment phone.
   */
  public function getAppointmentPhone() {
    return $this->get('appointment_phone')->value;
  }

  /**
   * Sets the appointment phone.
   *
   * @param string $phone
   *   The appointment phone.
   *
   * @return $this
   */
  public function setAppointmentPhone($phone) {
    $this->set('appointment_phone', $phone);
    return $this;
  }

  /**
   * Returns the appointment email.
   *
   * @return string
   *   The appointment email.
   */
  public function getAppointmentEmail() {
    return $this->get('appointment_email')->value;
  }

  /**
   * Sets the appointment email.
   *
   * @param string $email
   *   The appointment email.
   *
   * @return $this
   */
  public function setAppointmentEmail($email) {
    $this->set('appointment_email', $email);
    return $this;
  }

  public function getAppointmentDay() {
    $date_string = $this->get('appointment_day')->value;
    return \DateTime::createFromFormat('Y-m-d', $date_string);
  }

  /**
   * Sets the appointment day.
   *
   * @param \DateTime $date
   *   The appointment day.
   *
   * @return $this
   */
  public function setAppointmentDay(\DateTime $date) {
    $this->set('appointment_day', $date->format('Y-m-d'));
    return $this;
  }

  /**
   * Returns the appointment hour.
   *
   * @return string
   *   The appointment hour.
   */
  public function getAppointmentHour() {
    return $this->get('appointment_hour')->value;
  }

  /**
   * Sets the appointment hour.
   *
   * @param string $hour
   *   The appointment hour.
   *
   * @return $this
   */
  public function setAppointmentHour($hour) {
    $this->set('appointment_hour', $hour);
    return $this;
  }
}
