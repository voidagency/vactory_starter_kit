<?php

namespace Drupal\vactory_report_content\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for reported content entities.
 */
class ReportedContentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // Add user relationship
    /*$data['vactory_revisions']['user_id']['relationship'] = [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'relationship field' => 'user_id',
      'id' => 'standard',
      'label' => t('User'),
    ];*/

    return $data;
  }

}
