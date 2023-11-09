<?php

namespace Drupal\vactory_extended_seo\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Vactory extended seo entities.
 */
class VactoryExtendedSeoViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
