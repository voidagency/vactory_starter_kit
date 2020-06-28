<?php

namespace Drupal\vactory_appointment;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Class AppointmentsListBuilder
 *
 * @package Drupal\vactory_appointment
 */
class AppointmentsListBuilder extends EntityListBuilder {

  public function buildHeader() {
    $header['id'] = $this->t('Appointment Entity ID');
    $header['title'] = $this->t('Title');
    $header['agency'] = $this->t('Agency');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['title'] = Link::createFromRoute(
      $entity->label(),
      'entity.vactory_appointment.edit_form',
      ['vactory_appointment' => $entity->id()]
    );
    $row['agency'] = Link::createFromRoute(
      $entity->getAgency()->getName(),
      'entity.vactory_appointment.edit_form',
      ['vactory_appointment' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }
}
