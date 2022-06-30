<?php

namespace Drupal\vactory_report_content\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Class ReportedContentListBuilder
 *
 * @package Drupal\vactory_report_content
 */
class ReportedContentListBuilder extends EntityListBuilder {

  /**
   * {@inheritDoc}
   */
  public function buildHeader() {
    $header['path'] = $this->t('Reported Page');
    $header['id'] = $this->t('Id');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritDoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['path'] = $entity->get('path')->value;
    return $row + parent::buildRow($entity);
  }

}
