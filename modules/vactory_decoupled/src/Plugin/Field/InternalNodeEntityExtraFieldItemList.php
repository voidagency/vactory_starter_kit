<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\Url;

/**
 * Extra data per node.
 */
class InternalNodeEntityExtraFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  // phpcs:disable
  protected ?CacheableMetadata $cacheMetadata = NULL;
  // phpcs:enable

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityRepository = $container->get('entity.repository');
    $instance->languageManager = $container->get('language_manager');
    $instance->cacheMetadata = new CacheableMetadata();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    /** @var \Drupal\node\Entity\Node $entity */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if (!in_array($entity_type, ['node'])) {
      return;
    }

    if ($entity->isNew()) {
      return;
    }

    $value = [
      'translations' => $this->getTranslations($entity),
    ];

    $excluded = $entity->get('cache_exclude')->value;
    if (!$excluded) {
      $config = \Drupal::config('vactory_decoupled.settings');
      $excluded_types = $config->get('cache_excluded_types') ?? [];
      if (in_array($entity->bundle(), $excluded_types)) {
        $excluded = TRUE;
      }
    }
    $value['cache_exclude'] = (boolean) $excluded;
    $context = [
      'entity' => $entity,
    ];
    \Drupal::moduleHandler()->alter('decoupled_extra_field_value', $value, $context, $this->cacheMetadata);
    $this->cacheMetadata->addCacheTags([
      'config:vactory_decoupled.settings',
      'vactory_decoupled.switch_lang_settings',
    ]);
    $this->list[0] = $this->createItem(0, $value);
  }

  /**
   * {@inheritDoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = parent::access($operation, $account, TRUE);

    if ($return_as_object) {
      // phpcs:disable
      // Here you witness a pure hack. The thing is that JSON:API
      // Normalization does not compute cacheable metadata for
      // Computed relations like this one
      /** @see \Drupal\jsonapi\JsonApiResource\ResourceIdentifier */
      /** @see \Drupal\jsonapi\Normalizer\ResourceIdentifierNormalizer */
      // However, thanks to the access check, its result is added
      // As a cacheable dependency to the normalization.
      /** @see \Drupal\jsonapi\Normalizer\ResourceObjectNormalizer::serializeField() */
      // phpcs:enable
      $this->ensureComputedValue();
      \assert($this->cacheMetadata instanceof CacheableMetadata);
      $access->addCacheableDependency($this->cacheMetadata);

      return $access;
    }

    return $access->isAllowed();
  }

  /**
   * Get translations.
   */
  protected function getTranslations($entity) {
    $siteConfig = \Drupal::config('system.site');
    $switch_lang_settings = \Drupal::config('vactory_decoupled.switch_lang_settings');
    $hide_untranslated = $switch_lang_settings->get('hide_untranslated');
    $front_uri = $siteConfig->get('page.front');
    $internal_uri = "/node/" . $entity->id();

    $langcodes = $this->languageManager->getLanguages();
    $langcodesList = array_keys($langcodes);
    if ($hide_untranslated) {
      $langcodesList = array_filter($langcodesList, function ($langcode) use ($entity) {
        return $entity->hasTranslation($langcode);
      });
    }
    $data = [];

    // Frontpage special case.
    if ($front_uri === $internal_uri) {
      foreach ($langcodesList as $langcode) {
        $data[$langcode] =
        Url::fromRoute('<front>', [], [
          'language' => $this->languageManager->getLanguage($langcode),
        ])->toString();
      }
    }
    else {
      foreach ($langcodesList as $langcode) {
        if ($entity->hasTranslation($langcode)) {
          $translation = $this->entityRepository->getTranslationFromContext($entity, $langcode);
          $data[$langcode] =
            $translation->toUrl('canonical', [
              'language' => $translation->language(),
            ])->toString();
        }
        else {
          $data[$langcode] = "/" . $langcode . "/node/" . $entity->id();
        }

      }
    }

    return $data;
  }

}
