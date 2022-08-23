<?php

namespace Drupal\vactory_decoupled_router\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Route.
 */
class RouteListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('Machine name');
        $header['label'] = $this->t('Route');
        $header['path'] = $this->t('Path');
        $header['alias'] = $this->t('Alias');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        $row['id'] = $entity->id();
        $row['label'] = $entity->label();
        $row['path'] = $entity->getPath();
        $row['alias'] = $entity->getAlias();

        return $row + parent::buildRow($entity);
    }
}
