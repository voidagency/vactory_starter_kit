<?php

namespace Drupal\vactory_locator;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface LocatorEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Locator Entity revision IDs for a specific Locator Entity.
   *
   * @param \Drupal\vactory_locator\Entity\LocatorEntityInterface $entity
   *   The Locator Entity entity.
   *
   * @return int[]
   *   Locator Entity revision IDs (in ascending order).
   */
  public function revisionIds(LocatorEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Locator Entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Locator Entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\vactory_locator\Entity\LocatorEntityInterface $entity
   *   The Locator Entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(LocatorEntityInterface $entity);

  /**
   * Unsets the language for all Locator Entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
