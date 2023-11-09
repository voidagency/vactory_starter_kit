<?php

namespace Drupal\vactory_extended_seo;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Vactory extended seo entities.
 *
 * @ingroup vactory_extended_seo
 */
class VactoryExtendedSeoListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Vactory extended seo ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\vactory_extended_seo\Entity\VactoryExtendedSeo $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.vactory_extended_seo.edit_form',
      ['vactory_extended_seo' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
