<?php

namespace Drupal\vactory_revisions\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Vactory Revision entities.
 */
class VactoryRevisionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // Add user relationship
    $data['vactory_revisions']['user_id']['relationship'] = [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'relationship field' => 'user_id',
      'id' => 'standard',
      'label' => t('User'),
    ];

    return $data;
  }

}
