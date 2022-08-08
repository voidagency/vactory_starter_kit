<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * Extra data per node.
 */
class InternalNodeEntityExtraFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

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


    $this->list[0] = $this->createItem(0, $value);
  }

  protected function getTranslations($entity)
  {
    $siteConfig = \Drupal::config('system.site');
    $front_uri = $siteConfig->get('page.front');
    $internal_uri = "/node/" . $entity->id();

    $langcodes = \Drupal::languageManager()->getLanguages();
    $langcodesList = array_keys($langcodes);
    $data = [];

    // Frontpage special case.
    if ($front_uri === $internal_uri) {
      foreach ($langcodesList as $langcode) {
        $data[$langcode] =
        Url::fromRoute('<front>', [], [
          'language' => \Drupal::languageManager()->getLanguage($langcode),
        ])->toString();
      }
    }
    else {
      foreach ($langcodesList as $langcode) {
        if ($entity->hasTranslation($langcode)) {
          $translation = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);
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
