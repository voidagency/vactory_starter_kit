<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\metatag\MetatagManagerInterface;

/**
 * Vactory decoupled dev helper.
 */
class VactoryDecoupledHelper {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Metatag manager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    MetatagManagerInterface $metatagManager,
    LanguageManagerInterface $languageManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->metatagManager = $metatagManager;
    $this->languageManager = $languageManager;
  }

  public function metatagGetDefaultTags($entity = NULL) {
    $global_metatag_manager = $this->entityTypeManager->getStorage('metatag_defaults');

    // Load config based on language.
    $current_language = NULL;
    if ($entity !== NULL) {
      $current_language = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage($entity->language());
    }

    // First we load global defaults.
    $metatags = $this->metatagManager->getGlobalMetatags();
    if (!$metatags) {
      if ($current_language) {
        $this->languageManager->setConfigOverrideLanguage($current_language);
      }
      return NULL;
    }

    // Check if this is a special page.
    $special_metatags = $this->metatagManager->getSpecialMetatags();
    if (isset($special_metatags)) {
      $metatags->overwriteTags($special_metatags->get('tags'));
    }

    // Next check if there is this page is an entity that has meta tags.
    else {
      if (!$entity) {
        $entity = metatag_get_route_entity();
      }

      if (!empty($entity) && $entity instanceof ContentEntityInterface) {
        /** @var \Drupal\metatag\Entity\MetatagDefaults|null $entity_metatags */
        $entity_metatags = $global_metatag_manager->load($entity->getEntityTypeId());
        if ($entity_metatags != NULL && $entity_metatags->status()) {
          // Merge with global defaults.
          $metatags->overwriteTags($entity_metatags->get('tags'));
        }

        // Finally, check if bundle overrides should be added.
        /** @var \Drupal\metatag\Entity\MetatagDefaults|null $bundle_metatags */
        $bundle_metatags = $global_metatag_manager->load($entity->getEntityTypeId() . '__' . $entity->bundle());
        if ($bundle_metatags != NULL && $bundle_metatags->status()) {
          // Merge with existing defaults.
          $metatags->overwriteTags($bundle_metatags->get('tags'));
        }
      }
    }

    $tags = $metatags->get('tags');
    if ($current_language) {
      $this->languageManager->setConfigOverrideLanguage($current_language);
    }
    return $tags;
  }

}
