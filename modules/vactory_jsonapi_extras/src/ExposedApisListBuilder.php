<?php

namespace Drupal\vactory_jsonapi_extras;

use Drupal\vactory_jsonapi_extras\Entity\ApiPackages;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of exposed apises.
 */
class ExposedApisListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['path'] = $this->t('Path');
    $header['id'] = $this->t('Machine name');
    $header['packages'] = $this->t('Packages');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\vactory_jsonapi_extras\ExposedApisInterface $entity */
    $row['label'] = $entity->label();
    $row['path'] = $entity->path();
    $row['id'] = $entity->id();
    $packages = is_array($entity->packages()) ? $entity->packages() : [];
    $packages = array_filter($packages);
    $packages = array_map(function ($package_id) {
      $package = ApiPackages::load($package_id);
      return $package ? $package->label() : 0;
    }, $packages);
    $packages = array_filter($packages);
    $row['packages'] = implode(',', $packages);
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
