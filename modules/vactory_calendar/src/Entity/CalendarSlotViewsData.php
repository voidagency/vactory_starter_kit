<?php

namespace Drupal\vactory_calendar\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Calendar slot entities.
 */
class CalendarSlotViewsData extends EntityViewsData {

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
