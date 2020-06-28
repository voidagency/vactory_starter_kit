<?php

namespace Drupal\vactory_locator;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\vactory_locator\Entity\LocatorEntityInterface;

/**
 * Defines the storage handler class for Locator Entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Locator Entity entities.
 *
 * @ingroup vactory_locator
 */
class LocatorEntityStorage extends SqlContentEntityStorage implements LocatorEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(LocatorEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {locator_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {locator_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(LocatorEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {LocatorEntity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('locator_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
