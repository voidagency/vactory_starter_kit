<?php

namespace Drupal\vactory_jsonapi_extras;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of json:api packages.
 */
class ApiPackagesListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['routes'] = $this->t('Routes count');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\vactory_jsonapi_extras\VactoryJsonapiPackagesInterface $entity */
    $routes = \Drupal::entityTypeManager()->getStorage('exposed_apis')
      ->loadByProperties(['packages.*' => $entity->id()]);
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['routes'] = count($routes) . ' Routes';
    return $row + parent::buildRow($entity);
  }

}
