<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * Extra data per node.
 */
class InternalNodeEntityExtraFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * Language manager service.
   *
   * @var LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Entity repository service.
   *
   * @var EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityRepository = $container->get('entity.repository');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var Node $entity */
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

    $context = [
      'entity' => $entity,
    ];
    \Drupal::moduleHandler()->alter('decoupled_extra_field_value', $value, $context);

    $this->list[0] = $this->createItem(0, $value);
  }

  protected function getTranslations($entity)
  {
    $siteConfig = \Drupal::config('system.site');
    $front_uri = $siteConfig->get('page.front');
    $internal_uri = "/node/" . $entity->id();

    $langcodes = $this->languageManager->getLanguages();
    $langcodesList = array_keys($langcodes);
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
