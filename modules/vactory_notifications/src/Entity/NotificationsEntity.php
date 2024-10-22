<?php

namespace Drupal\vactory_notifications\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Class VactoryNotifications.
 *
 * @ingroup vactory_notifications
 * @ContentEntityType(
 *   id = "notifications_entity",
 *   label = @Translation("Notifications entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" =
 *   "Drupal\vactory_notifications\NotificationsListBuilder",
 *     "views_data" =
 *   "Drupal\vactory_notifications\Entity\NotificationsEntityViewsData",
 *     "translation" =
 *   "Drupal\vactory_notifications\NotificationsEntityTranslationHandler",
 *     "form" = {
 *       "add" = "Drupal\vactory_notifications\Form\NotificationsForm",
 *       "edit" = "Drupal\vactory_notifications\Form\NotificationsForm",
 *       "delete" =
 *   "Drupal\vactory_notifications\Form\NotificationsDeleteForm",
 *     },
 *     "access" =
 *   "Drupal\vactory_notifications\NotificationsAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "notifications_entity",
 *   data_table = "notifications_entity_field_data",
 *   admin_permission = "administer notifications",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" =
 *   "/admin/structure/notifications_entity/{notifications_entity}",
 *     "edit-form" =
 *   "/admin/structure/notifications_entity/{notifications_entity}/edit",
 *     "delete-form" =
 *   "/admin/structure/notifications_entity/{notifications_entity}/delete",
 *     "collection" = "/admin/structure/notifications_entity/list"
 *   },
 * )
 */
class NotificationsEntity extends ContentEntityBase implements NotificationsInterface {

  /**
   * Pre create.
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * Base field definitions.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Notification entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Notification entity.'))
      ->setReadOnly(TRUE);

    // The notification entity title.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Notification entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The notification owner (notification from which user).
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Notification owner'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Related notification content (notification from which node).
    $fields['notification_related_content'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Node'))
      ->setDescription(t('Choose the notification related node'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE)
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Notification details/message.
    $fields['notification_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notification message'))
      ->setDescription(t('Store the notification message content.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ])
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -1,
      ]);

    // Notification status (is it published or not).
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDescription(t('A boolean indicating whether the notification is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 9,
      ]);

    // Notification concerned users (which users should be notified).
    $fields['notification_concerned_users'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notification concerned users'))
      ->setDescription(t('A JSON array which store IDs of notification concerned users.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ]);

    // Notification viewers (users who have already viewed the notification).
    $fields['notification_viewers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notification viewers'))
      ->setDescription(t('A JSON array which store IDs of users who have already viewed the notification.'))
      ->setSettings([
        'default_value' => '',
        'text_processing' => 0,
      ]);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of Notifications entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Check if a user is concerned by current notification.
   */
  public function isUserConcerned($uid) {
    $concerned_users = $this->getConcernedUsers();
    return in_array($uid, $concerned_users);
  }

  /**
   * Check if current notification has been viewed by given user.
   */
  public function isViewedByUser($uid) {
    $notification_viewers = $this->getViewers();
    return in_array($uid, $notification_viewers);
  }

  /**
   * Check if current notification is published.
   */
  public function isPublished() {
    return (boolean) $this->get('status')->value;
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
   * Get current notification title.
   */
  public function getTitle() {
    return $this->get('name')->value;
  }

  /**
   * Set current notification title.
   */
  public function setTitle($title) {
    $this->set('name', $title);
    return $this;
  }

  /**
   * Get current notification message.
   */
  public function getMessage() {
    return $this->get('notification_message')->value;
  }

  /**
   * Set current notification message.
   */
  public function setMessage($message) {
    $this->set('notification_message', $message);
    return $this;
  }

  /**
   * Get current notification related content ID.
   */
  public function getRelatedContent() {
    return $this->get('notification_related_content')->target_id;
  }

  /**
   * Get notification concerned users.
   */
  public function getConcernedUsers() {
    return json_decode($this->get('notification_concerned_users')->value);
  }

  /**
   * Set notification concerned users.
   */
  public function setConcernedUsers(array $concerned_user) {
    $this->set('notification_concerned_users', json_encode($concerned_user));
    return $this;
  }

  /**
   * Get notification viewers.
   */
  public function getViewers() {
    return json_decode($this->get('notification_viewers')->value);
  }

  /**
   * Set notification viewers.
   */
  public function setViewers(array $viewers_ids) {
    $this->set('notification_viewers', json_encode($viewers_ids));
    return $this;
  }

}
