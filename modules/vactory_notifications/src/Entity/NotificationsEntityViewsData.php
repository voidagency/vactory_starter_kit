<?php

namespace Drupal\vactory_notifications\Entity;

use Drupal\views\EntityViewsData;

/**
 * Class NotificationsEntityViewsData
 *
 * @package Drupal\vactory_notifications\Entity
 */
class NotificationsEntityViewsData extends EntityViewsData {
  function getViewsData() {
    $viewsData = parent::getViewsData();
    if (isset($viewsData['notifications_entity_field_data'])) {
      $viewsData['notifications_entity_field_data']['notification_concerned_users']['filter']['id'] = 'notification_concerned_user';
    }
    return $viewsData;
  }
}
