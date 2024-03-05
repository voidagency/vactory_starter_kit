<?php

namespace Drupal\vactory_calendar;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Calendar slot entities.
 *
 * @ingroup vactory_calendar
 */
class CalendarSlotListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Calendar slot ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\vactory_calendar\Entity\CalendarSlot $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.calendar_slot.edit_form',
      ['calendar_slot' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
