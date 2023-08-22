<?php

namespace Drupal\vactory_calendar\Service;

use Drupal\vactory_calendar\Entity\CalendarSlot;

/**
 *
 */
interface TableReservationInterface {

  public const SEND_INVITATION = 'invitation_mail';

  public const SEND_CONFIRMATION = 'confirmation_mail';

  public const SEND_ANNULATION = 'annulation_mail';

  public const TABLE_RESERVED = 'table_reserved';

  /**
   * @param \Drupal\vactory_calendar\Entity\CalendarSlot $event
   *
   * @return mixed
   */
  public function assignTable(CalendarSlot $event);

  /**
   * @param int $id
   * @param \Drupal\Core\Entity\EntityInterface|null $slot
   *
   * @return mixed
   */
  public function freeTable(int $id, $slot = NULL);

  /**
   * @return mixed
   */
  public function countAvailable();

  /**
   * @param string $type
   * @param \Drupal\vactory_calendar\Entity\CalendarSlot|null $event
   *
   * @return mixed
   */
  public function notify(string $type, CalendarSlot $event = NULL);

}
