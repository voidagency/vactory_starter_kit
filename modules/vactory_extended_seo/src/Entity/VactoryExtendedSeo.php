<?php

namespace Drupal\vactory_extended_seo\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Drupal;
use Drupal\user\UserInterface;

/**
 * Defines the Vactory extended seo entity.
 *
 * @ingroup vactory_extended_seo
 *
 * @ContentEntityType(
 *   id = "vactory_extended_seo",
 *   label = @Translation("Vactory extended seo"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\vactory_extended_seo\VactoryExtendedSeoListBuilder",
 *     "views_data" = "Drupal\vactory_extended_seo\Entity\VactoryExtendedSeoViewsData",
 *     "translation" = "Drupal\vactory_extended_seo\VactoryExtendedSeoTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\vactory_extended_seo\Form\VactoryExtendedSeoForm",
 *       "add" = "Drupal\vactory_extended_seo\Form\VactoryExtendedSeoForm",
 *       "edit" = "Drupal\vactory_extended_seo\Form\VactoryExtendedSeoForm",
 *       "delete" = "Drupal\vactory_extended_seo\Form\VactoryExtendedSeoDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\vactory_extended_seo\VactoryExtendedSeoHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\vactory_extended_seo\VactoryExtendedSeoAccessControlHandler",
 *   },
 *   base_table = "vactory_extended_seo",
 *   data_table = "vactory_extended_seo_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer vactory extended seo entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/vactory_extended_seo/{vactory_extended_seo}",
 *     "add-form" = "/admin/structure/vactory_extended_seo/add",
 *     "edit-form" = "/admin/structure/vactory_extended_seo/{vactory_extended_seo}/edit",
 *     "delete-form" = "/admin/structure/vactory_extended_seo/{vactory_extended_seo}/delete",
 *     "collection" = "/admin/structure/vactory_extended_seo",
 *   },
 *   field_ui_base_route = "vactory_extended_seo.settings"
 * )
 */
class VactoryExtendedSeo extends ContentEntityBase implements VactoryExtendedSeoInterface {

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

    $languageManager = \Drupal::service('language_manager');
    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Vactory extended seo entity.'))
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
        'weight' => -10,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['node_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referenced Node'))
      ->setDescription(t('The node ID to be referenced by the Vactory extended seo entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'node',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -20,
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
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Vactory extended seo entity.'))
      ->setSettings([
        'max_length' => 50,
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
        'weight' => -50,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Vactory extended seo is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $activeLanguages = $languageManager->getLanguages();

    foreach ($activeLanguages as $idx => $lang) {
      $sanitizeId = str_replace('-', '_', $idx);
      $fields["alternate_$sanitizeId"] = BaseFieldDefinition::create('string')
        ->setLabel(t("Alternate $idx"))
        ->setDescription(t("The relative link to the $idx version"))
        ->setSettings([
          'max_length' => 200,
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
        ->setDisplayConfigurable('view', TRUE);
    }

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
