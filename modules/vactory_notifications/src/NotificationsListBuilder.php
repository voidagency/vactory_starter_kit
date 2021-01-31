<?php

namespace Drupal\vactory_notifications;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Class NotificationsListBuilder
 *
 * @package Drupal\vactory_notifications
 */
class NotificationsListBuilder extends EntityListBuilder {

  public function buildHeader() {
    $header['id'] = $this->t('Notification Entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.notifications_entity.edit_form',
      ['notifications_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }
}
