<?php

namespace Drupal\vactory_locator\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Locator Entity entities.
 */
class LocatorEntityViewsData extends EntityViewsData {

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
