<?php

namespace Drupal\vactory_decoupled_revalidator;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of revalidator entity types.
 */
class RevalidatorEntityTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    $header['revalidator'] = $this->t('Revalidator');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\vactory_decoupled_revalidator\RevalidatorEntityTypeInterface $entity */
    $row['id'] = $entity ? $entity->id() : '-';
    $row['revalidator'] = $entity ? $entity->getRevalidator()->getLabel() : '-';
    return $row + parent::buildRow($entity);
  }

}
